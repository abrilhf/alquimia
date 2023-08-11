<?php
namespace App\Http\Controllers;

use App\Mail\RecuperarContraMailable;
use App\Models\Usuarios;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContraOlvidadaController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    protected $passwordResetValidationRules = [
        'email' => 'required|email|exists:usuarios,email',
    ];
    
    public function sendRecoveryCode(Request $request)
    {
        $customMessages = [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa una dirección de correo electrónico válida.',
            'email.exists' => 'El correo electrónico no existe en nuestros registros.',
        ];

        $request->validate($this->passwordResetValidationRules, $customMessages);

        $user = Usuarios::where('email', $request->email)->first();

        $recoveryCode = Str::random(40);
        $expiresAt = Carbon::now()->addDay();

        DB::table('usuarios')
            ->where('usuario_id', $user->usuario_id)
            ->update([
                'recovery_code' => $recoveryCode,
                'recovery_code_expires_at' => $expiresAt,
            ]);

        $user->save();

        Mail::to($user->email)->send(new RecuperarContraMailable($recoveryCode));

        return redirect()->route('password.reset', ['code' => $recoveryCode])
            ->with('message.success', 'Se ha enviado un código de recuperación a tu dirección de correo electrónico. Puedes restablecer tu contraseña utilizando el código proporcionado en el correo electrónico.');
    }
}