<?php

namespace App\Http\Controllers;

use App\Http\Controllers\WordPress\AssemblaArticoloPayloadController;
use App\Http\Controllers\WordPress\CategoryController;
use App\Jobs\PublishArticleToWordPress;
use App\Validators\WordPressDraftValidator;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\UserDraft;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\WordPress\TagController;
class EditPostController extends Controller
{
    /**
     * Edit a post or publish a new one
     */
    public function editPost(Request $request): JsonResponse
    {
        $wpUrl = config('wordpress.url');
        if (!filter_var($wpUrl, FILTER_VALIDATE_URL)) {
            return response()->json([
                'status' => 'error',
                'message' => 'WORDPRESS_URL non è un URL valido. Verifica la configurazione.'
            ], 400);
        }

        // Handle both edit (wp_post_id) and new post (draft_id)
        $wpPostId = $request->input('wp_post_id');
        $draftId = $request->input('draft_id');
        
        if ($wpPostId) {
            // Edit existing post
            $userDraft = UserDraft::where('wp_post_id', $wpPostId)->first();
        } elseif ($draftId) {
            // Create new post
            $userDraft = UserDraft::findOrFail($draftId);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'wp_post_id o draft_id è richiesto'
            ], 400);
        }

        $validationError = WordPressDraftValidator::validate($request, $userDraft);
        if ($validationError) {
            return response()->json($validationError, 400);
        }

        $user = $this->localUser();
        
        // Check if localUser() returned an error response
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user; // Return the error response
        }
        
        // Use user ID 2 for WordPress publishing
        $publishUser = User::find(2);
        if (!$publishUser || !$publishUser->wordpress_token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utente di pubblicazione non configurato correttamente'
            ], 500);
        }
        $author = $publishUser->wp_user_id;
        $wpToken = $publishUser->wordpress_token;
        
        \Log::info('EditPostController token debug', [
            'publish_user_id' => $publishUser->id,
            'wp_user_id' => $publishUser->wp_user_id,
            'has_token' => (bool) $wpToken,
            'token_length' => $wpToken ? strlen($wpToken) : 0,
            'token_preview' => $wpToken ? substr($wpToken, 0, 10) . '...' : 'null'
        ]);
        // Determine publish URL based on whether it's edit or new post
        if ($wpPostId) {
            // Edit existing post
            $wpPublishUrl = rtrim($wpUrl, '/') . '/wp-json/wp/v2/posts/' . $wpPostId;
        } else {
            // Create new post
            $wpPublishUrl = rtrim($wpUrl, '/') . '/wp-json/wp/v2/posts';
        }

                // Assemble publication payload with all collected data
                $payload = [
                    'wordpress' => [
                        'url' => $wpUrl,
                        'publish_url' => $wpPublishUrl,
                        'token' => $wpToken,
                    ],
                    'user' => $publishUser,
                    'draft' => [
                        'id' => $userDraft->id,
                        'title' => $request->input('selected_title'),
                        'content' => $userDraft->content,
                        'description' => $userDraft->description,
                        'featured_image_url' => $userDraft->featured_image_url,
                        'seo_metadata' => $userDraft->seo_metadata,
                    ],
                    'publication' => [
                        'selected_title' => $request->input('selected_title'),
                        'selected_categories' => (array) $request->input('selected_categories', []),
                        'selected_tags' => (array) $request->input('selected_tags', []),
                    ],
                ];

                        // Send full payload to TagController for tag handling
        $tagsHandler = app(TagController::class)->main(new Request($payload));

        $categoryHandler = (new CategoryController())->map($request->input('selected_categories'));

        $imageRes = app(\App\Http\Controllers\WordPress\UploadMediaController::class)->uploadFromUrl(
            new Request([
                'wordpress' => ['url' => $wpUrl, 'token' => $wpToken],
                'image_url' => $userDraft->featured_image_url,
                'title' => $request->input('selected_title'),
            ])
        );
        $imageId = optional($imageRes->getData())->data->id ?? null;

                // Determine author based on whether it's edit or new post
                if ($wpPostId) {
                    // For updates, don't change the author - WordPress will preserve the original author
                    $userId = null;
                } else {
                    // For new posts, use the authenticated user's WordPress ID
                    $userId = $user->wp_user_id;
                }
        
                // Use the updated content from the request, fallback to draft content
                $updatedContent = $request->input('content', $userDraft->content);
                
                // Update the draft with the new content if provided
                if ($request->has('content')) {
                    $userDraft->update(['content' => $updatedContent]);
                }
                
                // invio dati al controller che assempla il payload per la pubblicazione
                $assembler = new AssemblaArticoloPayloadController(
                    $categoryHandler,
                    $tagsHandler,
                    $imageId,
                    $request->input('selected_title'),
                    $updatedContent,
                    'publish',
                    $userId
                );
                $articoloJson = $assembler->build();

                $article = $articoloJson->getData(true);
                
                \Log::info('EditPostController payload debug', [
                    'title' => $request->input('selected_title'),
                    'content_from_request' => $request->has('content'),
                    'content_length' => strlen($updatedContent),
                    'content_preview' => substr($updatedContent, 0, 100) . '...',
                    'draft_content_length' => strlen($userDraft->content),
                    'categories' => $categoryHandler,
                    'tags' => $tagsHandler,
                    'image_id' => $imageId,
                    'author_preserved' => 'WordPress will preserve original author',
                    'payload_keys' => array_keys($article)
                ]);
                $options = [
                'wordpress_url' => $wpUrl,
                'publish_url'   => $wpPublishUrl,
                'token'         => $wpToken,
                'draftId'       => $userDraft->id,
                ];
    
                PublishArticleToWordPress::dispatch($article, $options);
    
            // Dispatch job with assembled payload
            //PublishArticleToWordPress::dispatch($payload);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Job dispatched',
                'data' => [
                    'draft_id' => $userDraft->id
                ]
            ]);
    
    }

        /**
     * Return the authenticated local user's data (via Sanctum bearer token)
     */
    public function localUser(): User|JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utente non autenticato'
            ], 401);
        }

        return $user;
    }
}
