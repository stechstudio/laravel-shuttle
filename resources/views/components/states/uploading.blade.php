<div x-show="
        hasInternetConnection &&
        state === 'UPLOADING' &&
        overallProgress > 0
    "
>
    @lang('shuttle::shuttle.remaining')
</div>
