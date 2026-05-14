<x-auth-layout>
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white text-center">
                @lang('auth.deliveryExecutiveLoginViaOneTimePassword')
            </h2>
        </div>

        <x-validation-errors class="mb-4"/>

        @session('status')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ $value }}
        </div>
        @endsession

        @if(!session('otp_sent'))
            <form method="POST" action="{{ route('delivery.otp.send') }}">
                @csrf
                <div>
                    <x-label for="email" value="{{ __('app.email') }}"/>
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username"/>
                </div>

                <div class="flex items-center justify-between mt-6">
                    @if(!module_enabled('Subdomain') && !global_setting()->disable_landing_site)
                        <a href="{{ route('home') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">← @lang('auth.goHome')</a>
                    @else
                        <span></span>
                    @endif
                    <x-button type="submit" class="button">{{ __('auth.sendOtp') }}</x-button>
                </div>
            </form>
        @else
            <div id="otp-success-message" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400 text-center" style="display: none;"></div>

            <form method="POST" action="{{ route('delivery.otp.verify') }}">
                @csrf
                <input type="hidden" name="email" value="{{ session('email') }}">

                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                        {{ __('auth.weveSentA6DigitCodeTo') }}<br>
                        <span class="font-medium">{{ session('email') }}</span>
                    </p>
                </div>

                <div>
                    <x-label for="otp" value="{{ __('auth.enter6DigitCode') }}"/>
                    <div class="mt-1 flex justify-center space-x-2">
                        @for($i = 1; $i <= 6; $i++)
                            <input type="text" name="otp[]" maxlength="1" class="w-12 h-12 text-center text-lg font-semibold border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white otp-input" data-index="{{ $i }}" autocomplete="off">
                        @endfor
                    </div>
                    <input type="hidden" name="otp_combined" id="otp_combined">
                </div>

                <div class="flex items-center justify-between mt-6">
                    <button type="button" onclick="resendOtp()" class="text-sm text-blue-600 hover:text-blue-700" id="resendBtn" disabled>
                        {{ __('auth.resendOtp') }} (<span id="countdown">60</span>s)
                    </button>
                    <x-button type="submit" class="button">{{ __('auth.verifyOtp') }}</x-button>
                </div>
            </form>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('.otp-input');
            const otpCombined = document.getElementById('otp_combined');

            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = e.target.value;
                    if (!/^\d*$/.test(value)) {
                        e.target.value = '';
                        return;
                    }
                    if (value && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                    otpCombined.value = Array.from(otpInputs).map((el) => el.value).join('');
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });

            if (document.getElementById('resendBtn')) {
                startCountdown();
            }
        });

        function startCountdown() {
            let countdown = 60;
            const countdownElement = document.getElementById('countdown');
            const resendBtn = document.getElementById('resendBtn');

            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '{{ __('auth.resendOtp') }}';
                }
            }, 1000);
        }

        function resendOtp() {
            const email = '{{ session("email") }}';
            const resendBtn = document.getElementById('resendBtn');
            resendBtn.disabled = true;
            resendBtn.innerHTML = '{{ __('auth.sending') }}';

            fetch('{{ route("delivery.otp.resend") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: email })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const successMessage = document.getElementById('otp-success-message');
                        successMessage.textContent = '{{ __('auth.newOtpSentSuccessfully') }}';
                        successMessage.style.display = 'block';

                        const otpInputs = document.querySelectorAll('.otp-input');
                        otpInputs.forEach(input => input.value = '');
                        document.getElementById('otp_combined').value = '';
                        otpInputs[0].focus();
                        startCountdown();

                        setTimeout(() => {
                            successMessage.style.display = 'none';
                        }, 3000);
                    } else {
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = '{{ __('auth.resendOtp') }}';
                    }
                })
                .catch(() => {
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '{{ __('auth.resendOtp') }}';
                });
        }
    </script>
</x-auth-layout>
