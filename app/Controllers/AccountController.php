<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ControllerBase;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validator;

class AccountController extends ControllerBase
{
    public function index(Request $request): Response
    {
        $this->guard();

        return $this->view('account/dashboard', [
            'title' => 'My Account',
            'user' => $this->auth->user(),
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
        ]);
    }

    public function security(Request $request): Response
    {
        $this->guard();

        $user = $this->auth->user();
        $pending = $_SESSION['two_factor_pending'] ?? null;

        return $this->view('account/security', [
            'title' => 'Account Security',
            'user' => $user,
            'pending' => $pending,
            'success' => $this->getFlash('success'),
            'error' => $this->getFlash('error'),
        ]);
    }

    public function beginTwoFactor(Request $request): Response
    {
        $this->guard();
        $this->validateCsrf($request);

        $user = $this->auth->user();
        if (!$user) {
            return $this->redirect('/account');
        }

        if (!empty($user['two_factor_secret'])) {
            $this->flash('error', 'Two-factor authentication is already active. Disable it before creating a new setup.');

            return $this->redirect('/account/security');
        }

        $secret = $this->auth->generateTwoFactorSecret();
        $uri = $this->auth->provisioningUri((string) $user['email'], $secret);

        $_SESSION['two_factor_pending'] = [
            'secret' => $secret,
            'uri' => $uri,
            'qr' => $this->auth->twoFactorQrCode($uri),
        ];

        $this->flash('success', 'Scan the QR code with your authenticator app and enter the code to confirm.');

        return $this->redirect('/account/security');
    }

    public function confirmTwoFactor(Request $request): Response
    {
        $this->guard();
        $this->validateCsrf($request);

        $pending = $_SESSION['two_factor_pending'] ?? null;
        if (!$pending || !is_array($pending)) {
            $this->flash('error', 'There is no 2FA setup in progress.');
            return $this->redirect('/account/security');
        }

        $data = $request->all();
        $errors = Validator::make($data, [
            'two_factor_code' => 'required',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/account/security');
        }

        if (!$this->auth->verifyTwoFactor($pending['secret'], $data['two_factor_code'])) {
            $this->flash('error', 'The provided 2FA code is invalid.');
            return $this->redirect('/account/security');
        }

        $user = $this->auth->user();
        if ($user) {
            $this->auth->enableTwoFactor((int) $user['id'], $pending['secret']);
        }

        unset($_SESSION['two_factor_pending']);
        $this->flash('success', 'Two-factor authentication has been enabled.');

        return $this->redirect('/account/security');
    }

    public function disableTwoFactor(Request $request): Response
    {
        $this->guard();
        $this->validateCsrf($request);

        $data = $request->all();
        $errors = Validator::make($data, [
            'two_factor_code' => 'required',
        ]);

        if ($errors) {
            $this->flash('error', reset($errors));
            return $this->redirect('/account/security');
        }

        $user = $this->auth->user();
        if ($user && empty($user['two_factor_secret'])) {
            $this->flash('error', 'Two-factor authentication is not currently enabled.');

            return $this->redirect('/account/security');
        }

        if ($user && !empty($user['two_factor_secret']) && $this->auth->verifyTwoFactor((string) $user['two_factor_secret'], $data['two_factor_code'])) {
            $this->auth->disableTwoFactor((int) $user['id']);
            $this->flash('success', 'Two-factor authentication disabled.');
        } else {
            $this->flash('error', 'Unable to disable 2FA. Please confirm the code and try again.');
        }

        return $this->redirect('/account/security');
    }
}
