<!--suppress JSDuplicatedDeclaration, JSUnresolvedVariable -->
<div class="fixed bottom-0 inset-x-0">
    <div
        x-bind:class="{
            '{{ config(key: 'shuttle.colors.details-panel.uploading') }}': state === 'UPLOADING',
            '{{ config(key: 'shuttle.colors.details-panel.upload-success') }}': state === 'COMPLETE',
            '{{ config(key: 'shuttle.colors.details-panel.upload-error') }}': state === 'COMPLETE_WITH_ERRORS',
            '{{ config(key: 'shuttle.colors.details-panel.upload-error') }}': state === 'FAILED_WITH_ERRORS',
            '{{ config(key: 'shuttle.colors.details-panel.connection-lost') }}': state === 'CONNECTION_LOST',
        }"
        x-show="state !== 'IDLE'"
        class="px-6 py-3 text-white font-semibold flex items-center"
        style="display: none;"
    >
        <div class="mr-4">
            <div x-show="state === 'UPLOADING' && overallProgress === 0">
                @lang('shuttle::shuttle.preparing')
            </div>

            <div x-show="(state === 'UPLOADING' || state === 'CONNECTION_LOST') && overallProgress > 0">
                <span x-show="state === 'CONNECTION_LOST'">@lang('shuttle::shuttle.connection_lost')</span>

                <!--suppress JSUnresolvedFunction -->
                <span x-show="state === 'UPLOADING'" x-text="filesRemaining"></span> @lang('shuttle::shuttle.remaining')
            </div>

            <div x-show="state === 'COMPLETE'">
                @lang('shuttle::shuttle.finished_successfully')
            </div>

            <div x-show="state === 'COMPLETE_WITH_ERRORS'">
                @lang('shuttle::shuttle.finished_with_errors')
            </div>

            <div x-show="state === 'FAILED_WITH_ERRORS'">
                @lang('Something went wrong...')
            </div>
        </div>

        <div class="flex-grow">
            <div x-show="state === 'CONNECTION_LOST'" x-bind:style="'width: ' + overallProgress + '%'" class="h-1 bg-white"></div>
        </div>

        <div class="mx-4 w-12 text-right">
            <span x-text="overallProgress + '%'" x-show="overallProgress > 0"></span>
        </div>

        <div class="text-lg opacity-75 hover:opacity-100 cursor-pointer">
            <!--suppress JSUnresolvedFunction -->
            <svg
                @click="abort();"
                x-show="state === 'FAILED_WITH_ERRORS'"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>

            <!--suppress JSUnresolvedFunction -->
            <svg
                x-show="state !== 'IDLE' && state !== 'COMPLETE' && state !== 'FAILED_WITH_ERRORS' && ! showDetails"
                @click="setShowDetails(! showDetails);"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path>
            </svg>

            <!--suppress JSUnresolvedFunction -->
            <svg
                x-show="state !== 'IDLE' && state !== 'COMPLETE' && state !== 'FAILED_WITH_ERRORS' && showDetails"
                @click="setShowDetails(! showDetails);"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    <div x-cloak x-show="state !== 'IDLE'">
        <x-shuttle::uploads />
    </div>
</div>
