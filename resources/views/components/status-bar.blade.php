<div class="fixed bottom-0 inset-x-0">
    <div
        x-bind:class="{
            '{{ config('shuttle.colors.details-panel.uploading') }}': state === 'UPLOADING' ,
            '{{ config('shuttle.colors.details-panel.upload-success') }}': state === 'COMPLETE',
            '{{ config('shuttle.colors.details-panel.upload-error') }}': state === 'COMPLETE_WITH_ERRORS',
            '{{ config('shuttle.colors.details-panel.connection-lost') }}': state === 'CONNECTION_LOST',
        }"
        x-show="state !== 'IDLE'"
        class="px-6 py-3 text-white font-semibold flex items-center"
        style="display: none;"
    >
        <div class="mr-4">
            <div x-show="state === 'UPLOADING' && percent === 0">
                {{ trans(key: 'preparing') }}
            </div>

            <div x-show="(state === 'UPLOADING' || state === 'CONNECTION_LOST') && percent > 0">
                <span x-show="state === 'CONNECTION_LOST'">{{ trans(key: 'connection_lost') }}</span>

                <span x-text="filesRemaining()"></span> {{ trans(key: 'remaining') }}
            </div>

            <div x-show="state === 'COMPLETE'">
                {{ trans(key: 'finished_successfully') }}
            </div>

            <div x-show="state === 'COMPLETE_WITH_ERRORS'">
                {{ trans(key: 'finished_with_errors') }}
            </div>
        </div>

        <div class="flex-grow">
            <div x-bind:style="'width: ' + percent + '%'" class="h-1 bg-white"></div>
        </div>

        <div class="mx-4 w-12 text-right">
            <span x-text="percent + '%'" x-show="percent > 0"></span>
        </div>

        <div class="text-lg opacity-75 hover:opacity-100 cursor-pointer">
            <svg
                @click="showDetails = ! showDetails"
                x-show="! showDetails"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path>
            </svg>

            <svg
                @click="showDetails = ! showDetails"
                x-show="showDetails"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                class="w-6 h-6"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    <x-shuttle::uploads />
</div>
