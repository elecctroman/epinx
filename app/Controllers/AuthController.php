<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;
use App\Services\ReCaptchaService;
use PDOException;

class AuthController extends ControllerBase
{
    private ?ReCaptchaService $recaptcha = null;

    public function showLoginForm(Request $request): Response
    {
        return $this->view('auth/login', [
            'title' => 'Login',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'recaptcha' => $this->recaptchaConfig(),
        ]);
    }

    public function login(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $errors = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($message = $this->validateRecaptcha($request)) {
            $errors['captcha'] = $message;
        }

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/login');
        }

        $twoFactorCode = $data['two_factor_code'] ?? null;
        if (!$this->auth->attempt($data['email'], $data['password'], $twoFactorCode, $request->ip(), $request->userAgent())) {
            $this->flash('error', 'Invalid credentials, 2FA code, or too many attempts.');
            return $this->redirect('/login');
        }

        $this->flash('success', 'Welcome back!');

        return $this->redirect('/account');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();

        return $this->redirect('/');
    }

    public function showRegisterForm(Request $request): Response
    {
        return $this->view('auth/register', [
            'title' => 'Create Account',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'recaptcha' => $this->recaptchaConfig(),
        ]);
    }

    public function register(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $errors = Validator::make($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($message = $this->validateRecaptcha($request)) {
            $errors['captcha'] = $message;
        }

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/register');
        }

        try {
            $this->auth->register($data['name'], $data['email'], $data['password']);
        } catch (PDOException $exception) {
            $this->flash('error', 'Unable to create account: ' . $exception->getMessage());
            return $this->redirect('/register');
        }

        $this->flash('success', 'Account created successfully. You can now log in.');

        return $this->redirect('/login');
    }

    public function showForgotPasswordForm(Request $request): Response
    {
        return $this->view('auth/forgot-password', [
            'title' => 'Forgot Password',
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
            'recaptcha' => $this->recaptchaConfig(),
        ]);
    }

    public function sendResetLink(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $errors = Validator::make($data, [
            'email' => 'required|email',
        ]);

        if ($message = $this->validateRecaptcha($request)) {
            $errors['captcha'] = $message;
        }

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/password/forgot');
        }

        $token = $this->auth->createPasswordResetToken($data['email']);
        if ($token === null) {
            $this->flash('error', 'We could not find an account with that email address.');
            return $this->redirect('/password/forgot');
        }

        try {
            $this->auth->sendPasswordReset($data['email'], $token);
            $this->flash('success', 'Password reset instructions have been sent to your email.');
        } catch (\Throwable $throwable) {
            $this->flash('error', 'Failed to send reset email: ' . $throwable->getMessage());
        }

        return $this->redirect('/password/forgot');
    }

    public function showResetForm(Request $request): Response
    {
        return $this->view('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $request->query('token', ''),
            'email' => $request->query('email', ''),
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $this->validateCsrf($request);
        $data = $request->all();
        $errors = Validator::make($data, [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/password/reset?token=' . urlencode((string) $data['token']) . '&email=' . urlencode((string) $data['email']));
        }

        if (!$this->auth->resetPassword($data['email'], $data['token'], $data['password'])) {
            $this->flash('error', 'The reset token is invalid or has expired.');
            return $this->redirect('/password/reset?token=' . urlencode((string) $data['token']) . '&email=' . urlencode((string) $data['email']));
        }

        $this->flash('success', 'Password updated successfully. You can now log in.');

        return $this->redirect('/login');
    }

    private function recaptcha(): ReCaptchaService
    {
        if ($this->recaptcha === null) {
            $this->recaptcha = $this->container->has(ReCaptchaService::class)
                ? $this->container->get(ReCaptchaService::class)
                : new ReCaptchaService();
        }

        return $this->recaptcha;
    }

    private function isRecaptchaEnabled(): bool
    {
        return $this->recaptcha()->isEnabled();
    }

    private function recaptchaConfig(): array
    {
        return [
            'enabled' => $this->isRecaptchaEnabled(),
            'site_key' => Config::get('security.recaptcha.site_key'),
            'type' => Config::get('security.recaptcha.type', 'v2'),
        ];
    }

    private function validateRecaptcha(Request $request): ?string
    {
        if (!$this->isRecaptchaEnabled()) {
            return null;
        }

        try {
            $valid = $this->recaptcha()->verifyResponse($request->input('g-recaptcha-response'), $request->ip());
        } catch (\Throwable $throwable) {
            return 'reCAPTCHA verification failed: ' . $throwable->getMessage();
        }

        return $valid ? null : 'Please confirm you are not a robot.';
    }
}
