<div
    x-show="
        hasInternetConnection &&
        state === 'UPLOADING' &&
        overallProgress > 0
    "
    class="flex w-full items-full"
>
    <span x-text="filesRemaining" class="mr-1"></span>

    @lang('shuttle::shuttle.remaining')
</div>
