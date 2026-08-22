<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request, int $id, string $hash) {
    $user = User::query()->findOrFail($id);

    abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        $user->forceFill(['verification_status' => 'verified'])->save();
        event(new Verified($user));
    }

    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

    return redirect($frontendUrl.'/sell?email_verified=1');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
