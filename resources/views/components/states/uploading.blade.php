<div
        x-show="
        hasInternetConnection &&
        state === 'UPLOADING' &&
        overallProgress > 0
    "
        class="flex w-full items-full"
>
    <!-- @todo: filter incomplete files -->
    <span x-text="Object.keys(files).length" class="mr-1"></span>

    @lang('shuttle::shuttle.remaining')
</div>
