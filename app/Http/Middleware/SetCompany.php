<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

class SetCompany
{
    public function handle(

        Request $request,

        Closure $next

    ) {

        if (auth()->check()) {

            $user = auth()->user();

            $currentCompany = session()->has('company_id')
                ? $user
                    ->companies()
                    ->where(
                        'companies.id',
                        session('company_id')
                    )
                    ->first()
                : null;

            if (
                ! $currentCompany
                || strcasecmp(
                    trim($currentCompany->name),
                    'Default Company'
                ) === 0
            ) {

                $company = $user
                    ->companies()
                    ->where(
                        'companies.name',
                        '!=',
                        'Default Company'
                    )
                    ->first();

                if ($company) {

                    session()->put(

                        'company_id',

                        $company->id

                    );

                }

            }

        }

        $this->configureMailForCompany();

        return $next($request);
    }

    private function configureMailForCompany(): void
    {
        $companyId = session('company_id');

        if (! $companyId) {
            return;
        }

        $settings = \App\Models\Setting::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('group', 'mail')
            ->pluck('value', 'key');

        if (blank($settings->get('mail_host'))) {
            return;
        }

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.transport'  => 'smtp',
            'mail.mailers.smtp.host'       => $settings->get('mail_host'),
            'mail.mailers.smtp.port'       => $settings->get('mail_port', 465),
            'mail.mailers.smtp.encryption' => $settings->get('mail_encryption') ?: null,
            'mail.mailers.smtp.username'   => $settings->get('mail_username'),
            'mail.mailers.smtp.password'   => $settings->get('mail_password'),
            'mail.mailers.smtp.timeout'    => null,
            'mail.from.address'            => $settings->get('from_address') ?: $settings->get('mail_username'),
            'mail.from.name'               => $settings->get('from_name', config('app.name')),
        ]);
    }
}
