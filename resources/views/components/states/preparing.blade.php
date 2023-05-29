<div
    x-show="
        hasInternetConnection &&
        state === 'PREPARING' &&
        overallProgress === 0
    "
>
    @lang('shuttle::shuttle.preparing')
</div>
