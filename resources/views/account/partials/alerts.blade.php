{{-- Messages flash des pages du compte : account_success (confirmation) / account_error (refus) --}}
@if (session('account_success'))
    <div class="form-alert">{{ session('account_success') }}</div>
@endif
@if (session('account_error'))
    <div class="form-alert form-alert-error">{{ session('account_error') }}</div>
@endif
