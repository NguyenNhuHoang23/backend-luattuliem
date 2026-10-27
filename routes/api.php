<?php

use App\Http\Controllers\Api\AboutArticleController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\CompanySettingController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\HomePageController;
use App\Http\Controllers\Api\HomePageImageController;
use App\Http\Controllers\Api\MemberApplicationController;
use App\Http\Controllers\Api\MemberAuthController;
use App\Http\Controllers\Api\MemberCaseArticleController;
use App\Http\Controllers\Api\MemberCaseController;
use App\Http\Controllers\Api\MemberChatController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MemberTeamController;
use App\Http\Controllers\Api\NewsArticleController;
use App\Http\Controllers\Api\NewsArticleImageController;
use App\Http\Controllers\Api\PublicAboutArticleController;
use App\Http\Controllers\Api\PublicCaseArticleController;
use App\Http\Controllers\Api\PublicCompanySettingController;
use App\Http\Controllers\Api\PublicHomePageController;
use App\Http\Controllers\Api\PublicContactMessageController;
use App\Http\Controllers\Api\PublicNewsArticleController;
use App\Http\Controllers\Api\PublicRecruitmentApplicationController;
use App\Http\Controllers\Api\PublicRecruitmentPageController;
use App\Http\Controllers\Api\PublicRecruitmentPostController;
use App\Http\Controllers\Api\PublicRecruitmentRoleController;
use App\Http\Controllers\Api\RecruitmentPageController;
use App\Http\Controllers\Api\RecruitmentPostController;
use App\Http\Controllers\Api\RecruitmentRoleController;
use Illuminate\Support\Facades\Route;

Route::get('/gioi-thieu', [PublicAboutArticleController::class, 'index']);
Route::get('/gioi-thieu/{aboutArticle}', [PublicAboutArticleController::class, 'show']);
Route::get('/tin-tuc', [PublicNewsArticleController::class, 'index']);
Route::get('/tin-tuc/{newsArticle}', [PublicNewsArticleController::class, 'show']);
Route::get('/an-le', [PublicCaseArticleController::class, 'index']);
Route::get('/an-le/{caseArticle}', [PublicCaseArticleController::class, 'show']);
Route::get('/thong-tin', [PublicCompanySettingController::class, 'show']);
Route::get('/trang-chu', [PublicHomePageController::class, 'show']);
Route::get('/tuyen-dung', [PublicRecruitmentPageController::class, 'show']);
Route::get('/tuyen-dung/vi-tri', [PublicRecruitmentRoleController::class, 'index']);
Route::get('/tuyen-dung/vi-tri/{recruitmentRole}', [PublicRecruitmentRoleController::class, 'show']);
Route::get('/tuyen-dung/tin', [PublicRecruitmentPostController::class, 'index']);
Route::get('/tuyen-dung/tin/{recruitmentPost}', [PublicRecruitmentPostController::class, 'show']);
Route::post('/tuyen-dung/ho-so', [PublicRecruitmentApplicationController::class, 'store'])
    ->middleware('throttle:10,1');
Route::post('/lien-he', [PublicContactMessageController::class, 'store'])
    ->middleware('throttle:10,1');
Route::post('/thanh-vien/dang-nhap', [MemberAuthController::class, 'login'])
    ->middleware('throttle:10,1');
Route::middleware('auth.member')->group(function () {
    Route::get('/thanh-vien/me', [MemberAuthController::class, 'me']);
    Route::post('/thanh-vien/dang-xuat', [MemberAuthController::class, 'logout']);
    Route::get('/thanh-vien/doi-ngu', [MemberTeamController::class, 'index']);
    Route::get('/thanh-vien/doi-ngu/{member}', [MemberTeamController::class, 'show']);
    Route::get('/thanh-vien/de-nghi', [MemberApplicationController::class, 'index']);
    Route::get('/thanh-vien/de-nghi/{recruitmentApplication}/cv', [MemberApplicationController::class, 'cv']);
    Route::post('/thanh-vien/de-nghi/{recruitmentApplication}/duyet', [MemberApplicationController::class, 'approve']);
    Route::post('/thanh-vien/de-nghi/{recruitmentApplication}/tu-choi', [MemberApplicationController::class, 'reject']);
    Route::get('/thanh-vien/tin-nhan', [MemberChatController::class, 'index']);
    Route::post('/thanh-vien/tin-nhan/voi/{member}', [MemberChatController::class, 'open']);
    Route::get('/thanh-vien/tin-nhan/tep/{attachment}', [MemberChatController::class, 'download']);
    Route::get('/thanh-vien/tin-nhan/{conversation}/tin', [MemberChatController::class, 'messages']);
    Route::get('/thanh-vien/tin-nhan/{conversation}', [MemberChatController::class, 'show']);
    Route::post('/thanh-vien/tin-nhan/{conversation}/da-doc', [MemberChatController::class, 'markRead']);
    Route::post('/thanh-vien/tin-nhan/{conversation}', [MemberChatController::class, 'store']);
    Route::post('/thanh-vien/an-le', [MemberCaseArticleController::class, 'store']);
    Route::put('/thanh-vien/an-le/{caseArticle}', [MemberCaseArticleController::class, 'update']);
    Route::patch('/thanh-vien/an-le/{caseArticle}', [MemberCaseArticleController::class, 'update']);
    Route::delete('/thanh-vien/an-le/{caseArticle}', [MemberCaseArticleController::class, 'destroy']);
    Route::get('/thanh-vien/vu-viec', [MemberCaseController::class, 'index']);
    Route::post('/thanh-vien/vu-viec', [MemberCaseController::class, 'store']);
    Route::get('/thanh-vien/vu-viec/{conversation}', [MemberCaseController::class, 'show']);
    Route::put('/thanh-vien/vu-viec/{conversation}', [MemberCaseController::class, 'update']);
    Route::patch('/thanh-vien/vu-viec/{conversation}', [MemberCaseController::class, 'update']);
});
Route::get('/tin-tuc-anh/{filename}', [NewsArticleImageController::class, 'show'])
    ->where('filename', '[A-Za-z0-9._-]+');
Route::get('/trang-chu-anh/{filename}', [HomePageImageController::class, 'show'])
    ->where('filename', '[A-Za-z0-9._-]+');

Route::prefix('admin')->group(function () {
    Route::post('/dang-nhap', [AdminAuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware('auth.admin')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/dang-xuat', [AdminAuthController::class, 'logout']);

        Route::get('/gioi-thieu', [AboutArticleController::class, 'index']);
        Route::post('/gioi-thieu', [AboutArticleController::class, 'store']);
        Route::patch('/gioi-thieu/{aboutArticle}/status', [AboutArticleController::class, 'updateStatus']);
        Route::put('/gioi-thieu/{aboutArticle}', [AboutArticleController::class, 'update']);
        Route::patch('/gioi-thieu/{aboutArticle}', [AboutArticleController::class, 'update']);
        Route::delete('/gioi-thieu/{aboutArticle}', [AboutArticleController::class, 'destroy']);

        Route::get('/tin-tuc', [NewsArticleController::class, 'index']);
        Route::post('/tin-tuc', [NewsArticleController::class, 'store']);
        Route::post('/tin-tuc/{newsArticle}', [NewsArticleController::class, 'update']);
        Route::put('/tin-tuc/{newsArticle}', [NewsArticleController::class, 'update']);
        Route::patch('/tin-tuc/{newsArticle}', [NewsArticleController::class, 'update']);
        Route::delete('/tin-tuc/{newsArticle}', [NewsArticleController::class, 'destroy']);

        Route::get('/tuyen-dung', [RecruitmentPageController::class, 'show']);
        Route::put('/tuyen-dung', [RecruitmentPageController::class, 'update']);
        Route::patch('/tuyen-dung', [RecruitmentPageController::class, 'update']);

        Route::get('/tuyen-dung/vi-tri', [RecruitmentRoleController::class, 'index']);
        Route::post('/tuyen-dung/vi-tri', [RecruitmentRoleController::class, 'store']);
        Route::put('/tuyen-dung/vi-tri/{recruitmentRole}', [RecruitmentRoleController::class, 'update']);
        Route::patch('/tuyen-dung/vi-tri/{recruitmentRole}', [RecruitmentRoleController::class, 'update']);
        Route::delete('/tuyen-dung/vi-tri/{recruitmentRole}', [RecruitmentRoleController::class, 'destroy']);

        Route::get('/tuyen-dung/tin', [RecruitmentPostController::class, 'index']);
        Route::post('/tuyen-dung/tin', [RecruitmentPostController::class, 'store']);
        Route::put('/tuyen-dung/tin/{recruitmentPost}', [RecruitmentPostController::class, 'update']);
        Route::patch('/tuyen-dung/tin/{recruitmentPost}', [RecruitmentPostController::class, 'update']);
        Route::delete('/tuyen-dung/tin/{recruitmentPost}', [RecruitmentPostController::class, 'destroy']);

        Route::get('/thong-tin', [CompanySettingController::class, 'show']);
        Route::put('/thong-tin', [CompanySettingController::class, 'update']);
        Route::patch('/thong-tin', [CompanySettingController::class, 'update']);

        Route::get('/trang-chu', [HomePageController::class, 'show']);
        Route::put('/trang-chu', [HomePageController::class, 'update']);
        Route::patch('/trang-chu', [HomePageController::class, 'update']);
        Route::post('/trang-chu', [HomePageController::class, 'update']);

        Route::get('/lien-he', [ContactMessageController::class, 'index']);
        Route::get('/lien-he/{contactMessage}/files/{index}', [ContactMessageController::class, 'file'])
            ->whereNumber('index');
        Route::get('/lien-he/{contactMessage}', [ContactMessageController::class, 'show']);
        Route::patch('/lien-he/{contactMessage}/status', [ContactMessageController::class, 'updateStatus']);
        Route::delete('/lien-he/{contactMessage}', [ContactMessageController::class, 'destroy']);

        Route::get('/thanh-vien', [MemberController::class, 'index']);
        Route::post('/thanh-vien', [MemberController::class, 'store']);
        Route::get('/thanh-vien/{member}', [MemberController::class, 'show']);
        Route::put('/thanh-vien/{member}', [MemberController::class, 'update']);
        Route::patch('/thanh-vien/{member}', [MemberController::class, 'update']);
        Route::delete('/thanh-vien/{member}', [MemberController::class, 'destroy']);
    });
});
