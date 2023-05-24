<!--suppress JSDuplicatedDeclaration, JSUnresolvedVariable -->
<div class="fixed bottom-0 inset-x-0">
    <div
        x-bind:class="{
            '{{ config(key: 'shuttle.colors.details-panel.uploading') }}': $store.shuttle.state === 'UPLOADING' || $store.shuttle.state === 'RETRYING',
            '{{ config(key: 'shuttle.colors.details-panel.upload-success') }}': $store.shuttle.state === 'COMPLETE',
            '{{ config(key: 'shuttle.colors.details-panel.upload-error') }}': $store.shuttle.state === 'COMPLETE_WITH_ERRORS',
            '{{ config(key: 'shuttle.colors.details-panel.upload-error') }}': $store.shuttle.state === 'FAILED_WITH_ERRORS',
            '{{ config(key: 'shuttle.colors.details-panel.connection-lost') }}': $store.shuttle.state === 'CONNECTION_LOST',
        }"
        x-show="$store.shuttle.state !== 'IDLE'"
        class="px-6 py-3 text-white font-semibold flex items-center"
        style="display: none;"
    >
        <div class="mr-4">
            <div x-show="$store.shuttle.state === 'UPLOADING' && $store.shuttle.overallProgress === 0">
                {{ trans(key: 'shuttle::shuttle.preparing') }}
            </div>

            <div x-show="$store.shuttle.state === 'RETRYING' && $store.shuttle.overallProgress === 0">
                {{ trans(key: 'shuttle::shuttle.retrying') }}
            </div>

            <div x-show="($store.shuttle.state === 'UPLOADING' || $store.shuttle.state === 'CONNECTION_LOST') && $store.shuttle.overallProgress > 0">
                <span x-show="$store.shuttle.state === 'CONNECTION_LOST'">{{ trans(key: 'shuttle::shuttle.connection_lost') }}</span>

                <!--suppress JSUnresolvedFunction -->
                <span x-text="$store.shuttle.filesRemaining"></span> {{ trans(key: 'shuttle::shuttle.remaining') }}
            </div>

            <div x-show="$store.shuttle.state === 'COMPLETE'">
                {{ trans(key: 'shuttle::shuttle.finished_successfully') }}
            </div>

            <div x-show="$store.shuttle.state === 'COMPLETE_WITH_ERRORS'">
                {{ trans(key: 'shuttle::shuttle.finished_with_errors') }}
            </div>

            <div x-show="$store.shuttle.state === 'FAILED_WITH_ERRORS'">
                @lang('Something went wrong...')
            </div>
        </div>

        <div x-show="$store.shuttle.state !== 'FAILED_WITH_ERRORS'" class="flex-grow">
            <div x-bind:style="'width: ' + $store.shuttle.overallProgress + '%'" class="h-1 bg-white"></div>
        </div>

        <div x-show="$store.shuttle.state !== 'FAILED_WITH_ERRORS'" class="mx-4 w-12 text-right">
            <span x-text="$store.shuttle.overallProgress + '%'" x-show="$store.shuttle.overallProgress > 0"></span>
        </div>

        <div x-show="$store.shuttle.state !== 'FAILED_WITH_ERRORS'" class="text-lg opacity-75 hover:opacity-100 cursor-pointer">
            <!--suppress JSUnresolvedFunction -->
            <svg
                @click="abort();"
                x-show="$store.shuttle.state === 'FAILED_WITH_ERRORS'"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>

            <!--suppress JSUnresolvedFunction -->
            <svg
                @click="$store.shuttle.toggleShowDetails(! $store.shuttle.showDetails);"
                x-show="$store.shuttle.state !== 'FAILED_WITH_ERRORS' && ! $store.shuttle.showDetails"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path>
            </svg>

            <!--suppress JSUnresolvedFunction -->
            <svg
                @click="$store.shuttle.toggleShowDetails(! $store.shuttle.showDetails);"
                x-show="$store.shuttle.state !== 'FAILED_WITH_ERRORS' && $store.shuttle.showDetails"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    <div x-cloak x-show="$store.shuttle.state !== 'IDLE'">
        <x-shuttle::uploads />
    </div>
</div>
