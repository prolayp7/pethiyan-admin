<?php

namespace App\Http\Controllers;

use App\Traits\AuthTrait;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\AdminUser;
use App\Models\User;

class PasswordResetController extends Controller
{
    use AuthTrait, PanelAware;

    /**
     * Show the forgot password form
     */
    public function showForgotPasswordForm(): View
    {
        return view($this->panelView('auth.forgot-password'));
    }

    /**
     * Handle forgot password request
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $panel = $this->getPanel();
        $broker = $panel === 'admin' ? 'admins' : 'users';

        // Find the user by email in the active panel model.
        $user = $panel === 'admin'
            ? AdminUser::where('email', $request->email)->first()
            : User::where('email', $request->email)->first();

        // Prevent account enumeration: always return a success-style response,
        // but only send the reset link for valid users of the active panel.
        if ($user && $this->validateUserPanel($user)) {
            $token = Password::broker($broker)->createToken($user);
            $notificationClass = $this->getNotificationClass();
            $user->notify(new $notificationClass($token));
        }

        if ($request->expectsJson()) {
            return ApiResponseType::sendJsonResponse(
                true,
                __('passwords.sent'),
                []
            );
        }

        return back()->with('status', __('passwords.sent'));
    }

    /**
     * Show the reset password form
     */
    public function showResetPasswordForm(Request $request, string $token): View
    {
        return view($this->panelView('auth.reset-password'), [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        $broker = $this->getPanel() === 'admin' ? 'admins' : 'users';

        $status = Password::broker($broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($this->getLoginRoute() === 'login') {
                return redirect(config('app.frontendUrl'));
            }
            return redirect()->route($this->getLoginRoute())->with('status', __($status));
        }

        return back()->withErrors(['email' => [__($status)]]);
    }
}
