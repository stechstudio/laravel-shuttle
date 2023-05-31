<div class="fixed bottom-0 inset-x-0">
    <div class="p-4 bg-white text-black">
        <h1 x-text="Object.keys(files).length"></h1>
        <h1 x-text="state"></h1>
        <h1 x-text="hasInternetConnection"></h1>
    </div>

    <div
            x-bind:class="{
            '{{ config('shuttle.colors.details-panel.uploading') }}': hasInternetConnection && state === 'UPLOADING',
            '{{ config('shuttle.colors.details-panel.success') }}': success && Object.keys(files).length === 0,
            '{{ config('shuttle.colors.details-panel.error') }}': state === 'ERROR',
            '{{ config('shuttle.colors.details-panel.connection-lost') }}': ! hasInternetConnection,
        }"
            class="px-6 py-3 text-white font-semibold flex items-center"
    >
        <div class="mr-4">
            <x-shuttle::states.preparing/>

            <x-shuttle::states.uploading/>

            <x-shuttle::states.success/>

            <x-shuttle::states.error/>

            <x-shuttle::states.connection-lost/>
        </div>

        <x-shuttle::progress-bar/>
    </div>

    <div
            x-cloak
            x-show="state !== 'IDLE'"
    >
        <x-shuttle::uploads/>
    </div>
</div>
