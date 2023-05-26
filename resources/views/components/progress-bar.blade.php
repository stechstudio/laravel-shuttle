<div x-show="hasInternetConnection && state === 'UPLOADING'">
    <div x-bind:style="'width: ' + overallProgress + '%'" class="h-1 bg-white"></div>

    <div x-show="hasInternetConnection" class="mx-4 w-12 text-right">
        <span x-text="overallProgress + '%'" x-show="overallProgress > 0"></span>
    </div>
</div>
