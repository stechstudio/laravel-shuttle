<div x-show="
        hasInternetConnection &&
        state === 'UPLOADING' &&
        overallProgress > 0
    "
>
    <span x-text="filesRemaining"></span>

    @lang('shuttle::shuttle.remaining')
</div>
