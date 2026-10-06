<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorController extends Controller
{
    public function setup()
    {
        $user = auth()->user();
        
        $is_enabled = $user->mfa_enabled;

        $google2fa = new Google2FA();
        
        // Generate secret if one doesn't exist
        if (!$user->mfa_secret) {
            $user->mfa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeDataUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->mfa_secret
        );

        $qrCodeUrl = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_MARKUP_SVG,
            'imageBase64' => false,
        ])))->render($qrCodeDataUrl);

        // Strip the XML declaration to prevent browser parsing errors when inlined
        $qrCodeUrl = preg_replace('/<\?xml.*?\?>\s*/i', '', $qrCodeUrl);

        if (request()->expectsJson()) {
            return response()->json([
                'qrCodeUrl' => $qrCodeUrl,
                'secret' => $user->mfa_secret,
                'is_enabled' => $is_enabled
            ]);
        }

        if ($user->domain === 'system_user') {
            return view('admin.auth.2fa-setup', [
                'qrCodeUrl' => $qrCodeUrl,
                'secret' => $user->mfa_secret,
                'is_enabled' => $is_enabled,
                'layout' => 'components.layouts.admin'
            ]);
        }

        return view('portal.spa');
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $user = auth()->user();
        $google2fa = new Google2FA();
        
        $valid = $google2fa->verifyKey($user->mfa_secret, $request->one_time_password);

        if ($valid) {
            $user->mfa_enabled = true;
            $user->save();

            // Mark session as verified
            $request->session()->put('2fa_verified', true);
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Two-Factor Authentication has been successfully enabled.']);
            }
            
            $redirectUrl = $user->domain === 'system_user' ? route('admin.dashboard') : route('portal.spa');
            return redirect()->intended($redirectUrl)->with('success', 'Two-Factor Authentication has been successfully enabled.');
        }

        if ($request->expectsJson()) {
            return response()->json(['errors' => ['one_time_password' => ['Invalid authentication code. Please try again.']]], 422);
        }

        return back()->withErrors(['one_time_password' => 'Invalid authentication code. Please try again.']);
    }

    public function disable(Request $request)
    {
        $user = auth()->user();
        $user->mfa_enabled = false;
        $user->mfa_secret = null;
        $user->save();

        session()->forget('2fa_verified');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Two-Factor Authentication has been disabled.']);
        }

        return back()->with('success', 'Two-Factor Authentication has been disabled.');
    }
    
    public function challenge()
    {
        if (session('2fa_verified')) {
            $redirectUrl = auth()->user()->domain === 'system_user' ? route('admin.dashboard') : route('portal.spa');
            return redirect()->intended($redirectUrl);
        }
        
        return view('admin.auth.2fa-challenge');
    }
    
    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ]);

        $user = auth()->user();
        $google2fa = new Google2FA();
        
        $valid = $google2fa->verifyKey($user->mfa_secret, $request->one_time_password);

        if ($valid) {
            $request->session()->put('2fa_verified', true);
            $redirectUrl = $user->domain === 'system_user' ? route('admin.dashboard') : route('portal.spa');
            return redirect()->intended($redirectUrl);
        }

        return back()->withErrors(['one_time_password' => 'Invalid authentication code. Please try again.']);
    }
}
