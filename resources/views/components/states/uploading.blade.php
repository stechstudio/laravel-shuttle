<div
    x-show="
        hasInternetConnection &&
        state === 'UPLOADING'
    "
    class="flex w-full items-full"
>
    <span x-text="Object.keys(files).length" class="mr-1"></span>

    @lang('shuttle::shuttle.remaining')
</div>
