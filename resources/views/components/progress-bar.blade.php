<div
    x-show="hasInternetConnection && state === 'UPLOADING'"
    class="flex w-full items-center"
>
    <div x-bind:style="'width: ' + overallProgress + '%'" class="h-1 bg-white"></div>
</div>
