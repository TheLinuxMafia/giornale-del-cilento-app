<?php

namespace App\Http\Controllers\WordPress;

use App\Http\Controllers\Controller;
use App\Jobs\PublishArticleToWordPress;
use App\Models\UserDraft;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Validators\WordPressDraftValidator;
use App\Http\Controllers\WordPress\TagController;

class PostController extends Controller
{
    /**
     * Publish a new article
     */
    public function publishArticle(Request $request)
    {
        $wpUrl = config('wordpress.url');
        if (!filter_var($wpUrl, FILTER_VALIDATE_URL)) {
            return response()->json([
                'status' => 'error',
                'message' => 'WORDPRESS_URL non è un URL valido. Verifica la configurazione.'
            ], 400);
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
        $wpPublishUrl = rtrim($wpUrl, '/') . '/wp-json/wp/v2/posts';
        
        // Validate draft data
        $draft = UserDraft::findOrFail($request->input('draft_id'));
        $validationError = WordPressDraftValidator::validate($request, $draft);
        if ($validationError) {
            return response()->json($validationError, 400);
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
                'id' => $draft->id,
                'title' => $request->input('selected_title'),
                'content' => $draft->content,
                'description' => $draft->description,
                'featured_image_url' => $draft->featured_image_url,
                'seo_metadata' => $draft->seo_metadata,
            ],
            'publication' => [
                'selected_title' => $request->input('selected_title'),
                'selected_categories' => (array) $request->input('selected_categories', []),
                'selected_tags' => (array) $request->input('selected_tags', []),
            ],
        ];

        // Send full payload to TagController for tag handling
        $tagsHandler = app(TagController::class)->main(new Request($payload));

        $categoryHandler = (new \App\Http\Controllers\WordPress\CategoryController())->map($request->input('selected_categories'));

        $imageRes = app(\App\Http\Controllers\WordPress\UploadMediaController::class)->uploadFromUrl(
            new Request([
                'image_url' => $draft->featured_image_url,
                'title' => $request->input('selected_title'),
            ])
        );
        $imageId = optional($imageRes->getData())->data->id ?? null;

        // Use the WordPress user ID from the original user (not the publish user)
        $userId = $user->wp_user_id;

        // invio dati al controller che assempla il payload per la pubblicazione
        $assembler = new \App\Http\Controllers\WordPress\AssemblaArticoloPayloadController(
            $categoryHandler,
            $tagsHandler,
            $imageId,
            $request->input('selected_title'),
            $draft->content,
            'publish',
            $userId
        );
        $articoloJson = $assembler->build();

        Log::info($articoloJson->getContent());

        $article = $articoloJson->getData(true);
        $options = [
            'wordpress_url' => $wpUrl,
            'publish_url'   => $wpPublishUrl,
            'token'         => $wpToken,
            'draftId'       => $draft->id,
        ];

        PublishArticleToWordPress::dispatch($article, $options);

        return response()->json([
            'status' => 'success',
            'message' => 'Job dispatched',
            'data' => [
                'draft_id' => $draft->id
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

    /**
     * Test WordPress connection
     */
    public function testConnection()
    {
        return response()->json(['status' => 'success', 'message' => 'Connection test endpoint']);
    }

    /**
     * Get WordPress user info
     */
    public function getWordPressUserInfo()
    {
        return response()->json(['status' => 'success', 'message' => 'User info endpoint']);
    }
}