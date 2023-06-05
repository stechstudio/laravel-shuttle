<div x-cloak class="fixed bottom-0 inset-x-0">
    <div
        x-bind:class="{
            '{{ config('shuttle.colors.details-panel.uploading') }}': hasInternetConnection && state === 'UPLOADING',
            '{{ config('shuttle.colors.details-panel.success') }}': success && Object.keys(files).length === 0,
            '{{ config('shuttle.colors.details-panel.error') }}': state === 'ERROR',
            '{{ config('shuttle.colors.details-panel.connection-lost') }}': ! hasInternetConnection,
        }"
        class="px-6 py-3 text-white font-semibold flex items-center"
    >
        <div class="flex w-full items-center">
            <div class="mr-4">
                <x-shuttle::states.uploading />

                <x-shuttle::states.success />

                <x-shuttle::states.connection-lost />
            </div>

            <x-shuttle::progress-bar />

            <div class="mx-4 w-12 text-right">
                <span x-text="overallProgress + '%'" x-show="overallProgress > 0"></span>
            </div>

            <div x-show="state === 'UPLOADING'" class="text-lg opacity-75 hover:opacity-100 cursor-pointer">
                <!--suppress JSUnresolvedFunction -->
                <svg
                    @click="setShowDetails(! showDetails);"
                    x-show="showDetails"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    class="w-6 h-6"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path>
                </svg>

                <!--suppress JSUnresolvedFunction -->
                <svg
                    @click="setShowDetails(! showDetails);"
                    x-show="! showDetails"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    class="w-6 h-6"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
    </div>

    <x-shuttle::uploads />
</div>
