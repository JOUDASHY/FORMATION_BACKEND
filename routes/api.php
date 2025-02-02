<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PostforumController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\InscriptionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\VideoConferenceController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ContactResponseController;
use App\Http\Controllers\RoomController;

Route::apiResource('rooms', RoomController::class);
Route::post('/contacts/{contactId}/responses', [ContactResponseController::class, 'store']);
Route::get('/contacts/{contactId}/responses', [ContactResponseController::class, 'index']);
Route::post('/presences/mark', [PresenceController::class, 'markPresence']);
Route::get('/presences/planning/{planningId}', [PresenceController::class, 'getPresencesByPlanning']);
Route::put('/presences/{planningId}/{userId}', [PresenceController::class, 'updatePresenceStatus']);

Route::apiResource('lessons', LessonController::class);
Route::apiResource('certifications', CertificationController::class);
Route::apiResource('contacts', ContactController::class);
Route::apiResource('inscriptions', InscriptionController::class);
Route::apiResource('plannings', PlanningController::class);
Route::apiResource('courses', CourseController::class);
Route::apiResource('modules', ModuleController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('formations', FormationController::class);
Route::post('/messages/send', [MessageController::class, 'sendMessage']);
Route::post('/sendMessageFormation', [MessageController::class, 'sendMessageFormation']);
Route::post('/messages/{id}/mark-as-read', [MessageController::class, 'markAsRead']);
Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);

Route::get('/messages', [MessageController::class, 'getMessages']);
Route::get('/conversations', [MessageController::class, 'getConversations']);
Route::get('/CountConversationsNotRead', [MessageController::class, 'CountConversationsNotRead']);

Route::get('/indexForUser', [CertificationController::class, 'indexForUser']);
Route::get('/certificationByFormation', [CertificationController::class, 'certificationByFormation']);
Route::get('/InscriptionbyFomation', [InscriptionController::class, 'InscriptionbyFomation']);


Route::post('updateformation/{id}',[FormationController::class, "updateformation"]);
Route::get('PresentationFormation',[FormationController::class, "PresentationFormation"]);

Route::post('updateuser/{id}',[ UserController::class, "updateuser"]);
Route::post('updatecourse/{id}',[CourseController::class, "updatecourse"]);
Route::post('updatemodule/{id}',[ModuleController::class, "updatemodule"]);
Route::post('updatelesson/{id}',[LessonController::class, "updatelesson"]);
Route::get('Lesson_apprenant',[LessonController::class, "Lesson_apprenant"]);
Route::get('Planning_apprenant',[PlanningController::class, "Planning_apprenant"]);
Route::get('Planning_formateur',[PlanningController::class, "Planning_formateur"]);
Route::get('Lesson_formateur',[LessonController::class, "Lesson_formateur"]);
Route::get('Formation_apprenant',[FormationController::class, "Formation_apprenant"]);
Route::get('Formation_formateur',[FormationController::class, "Formation_formateur"]);
Route::post('/auth/forgot-password', [AuthController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/set-password', [AuthController::class, 'setPassword']);
Route::post('users/{id}/set-password', [UserController::class, 'setPassword']);
Route::post('courses/{courseId}/lessons/{id}',[LessonController::class, 'updatelesson']);
Route::get('auth/facebook', [AuthController::class, 'redirectToFacebook']);
Route::get('auth/facebook/callback', [AuthController::class, 'handleFacebookCallback']);
Route::group(
    ['middleware' =>'api','prefix' =>'auth'],function($router){
        Route::post('/login',[AuthController::class,'login']);
        Route::post('/register',[AuthController::class,'register']);
        Route::post('/logout',[AuthController::class,'logout']);
        Route::post('/refresh',[AuthController::class,'refresh']);
        Route::get('/me',[AuthController::class,'me']);
        Route::post('/user-profile',[AuthController::class,'userProfile']);
        Route::post('password/email', [AuthController::class, 'sendResetLinkEmail']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    }
);
Route::apiResource('postforums', PostForumController::class);
Route::post('updatepostforum/{id}', [PostForumController::class, 'updatepostforum']);
Route::apiResource('comments', CommentController::class);
Route::post('updatecomment/{id}', [CommentController::class, 'updatecomment']);
Route::apiResource('replies', ReplyController::class);
Route::post('updatereply/{id}', [ReplyController::class, 'updatereply']);
Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::post('/evaluations', [EvaluationController::class, 'store']);
Route::get('/evaluations/user/{formation_id}', [EvaluationController::class, 'show']);
Route::put('/evaluations/{id}', [EvaluationController::class, 'update']);    
Route::delete('/evaluations/{id}', [EvaluationController::class, 'destroy']);    
Route::get('/evaluations/apprenant', [EvaluationController::class, 'apprenant_evaluation']);
Route::get('formations/{formation_id}/users', [InscriptionController::class, 'getUsersByFormation']);
Route::get('/apprenant_inscription', [InscriptionController::class, 'apprenant_inscription']);
Route::get('/evaluations/{formation_id}/{course_id}', [EvaluationController::class, 'getEvaluationByFormationAndCourse']);


Route::post('/payment', [StripeController::class, 'processPayment']);Route::post('/inscriptions/{id}/paiement', [InscriptionController::class, 'addPaiement']);
Route::post('/inscriptions/{id}/paiement', [InscriptionController::class, 'addPaiement']);
Route::get('/paiements', [InscriptionController::class, 'getPaiements']);
Route::get('/courses_formateur', [CourseController::class, 'getCoursesByTeacher']);
Route::get('/coursesByFormation_formateur/{formation_id}', [CourseController::class, 'coursesByFormation_formateur']);
Route::get('/coursesByFormation/{formation_id}', [CourseController::class, 'coursesByFormation']);



Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/call-back', [AuthController::class, 'handleGoogleCallback']);
Route::post('auth/google/token', [AuthController::class, 'handleGoogleToken']);

Route::get('/keep-alive', function () {
    return response()->json(['status' => 'alive'], 200);
});