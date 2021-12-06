@props(['trigger' => false, 'dropTarget' => 'body', 'config' => '{}'])

<div class="drop-target absolute inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
    <div class="text-2xl xl:text-4xl text-white font-bold text-center">
        <i class="fas fa-cloud-upload fa-3x opacity-50 mb-3"></i>

        <div>Drop files to upload</div>
    </div>
</div>

<div x-title="shuttle"
    x-data="Shuttle({
        uploadContext: @entangle('uploadContext'),
        dropTarget: {{ json_encode($dropTarget) }},
        baseUrl: {{ json_encode(Shuttle::baseUrl()) }},
        ...{{ $config }},
    })"
    x-on:select-files.window="document.querySelector('.uppy-trigger').click(); if ('activeElement' in document) document.activeElement.blur();">
    <div class="absolute inset-x-0 bottom-0 z-50" wire:ignore>
        <input type="file" name="files[]" class="hidden uppy-trigger" multiple="true" x-on:change="loadFiles($event)"/>

        <div class="px-6 py-3 text-white font-semibold flex items-center" style="display: none"
             x-bind:class="{ 'bg-primary-500': state == 'UPLOADING' , 'bg-green-500': state == 'COMPLETE', 'bg-primary-700': state == 'COMPLETE_WITH_ERRORS' }"
             x-show.transition="state != 'IDLE'">
            <div class="mr-4">
                <div x-show="state == 'UPLOADING' && percent == 0">Preparing...</div>
                <div x-show="state == 'UPLOADING' && percent > 0"><span x-text="filesRemaining()"></span> remaining</div>
                <div x-show="state == 'COMPLETE'">Finished successfully</div>
                <div x-show="state == 'COMPLETE_WITH_ERRORS'">Finished with errors</div>
            </div>
            <div class="flex-grow"><div class="h-1 bg-white" x-bind:style="'width: ' + percent + '%'"></div></div>
            <div class="mx-4 w-12 text-right">
                <span x-text="percent + '%'" x-show="percent > 0"></span>
            </div>
            <div class="text-lg opacity-75 hover:opacity-100 cursor-pointer">
                <i class="fad fa-chevron-double-up" x-show="!showDetails" @click="showDetails = !showDetails"></i>
                <i class="fad fa-chevron-double-down" x-show="showDetails" @click="showDetails = !showDetails"></i>
            </div>
        </div>
        <div class="bg-white divide-y divide-gray-200 text-sm xl:text-base text-gray-700 max-h-48 overflow-y-auto">
            <template x-for="[id, file] of Object.entries(files)" :key="id">
                <div class="flex justify-between items-center px-4 py-3" x-show.transition="showDetails || file.status == 'error'">
                    <div class="flex items-center">
                        <img src="/images/spinner-dual-ring.svg" class="w-6 h-6" x-show="file.status == 'uploading'"/>
                        <i class="far fa-check text-green-500 w-6" x-show="file.status == 'complete'"></i>
                        <i class="far fa-exclamation-circle text-red-500 w-6" x-show="file.status == 'error'"></i>
                        <div class="text-gray-700 ml-2" x-text="file.name"></div>
                    </div>
                    <div class="flex items-center">
                        <div x-text="file.progress"></div>%
                        <div class="w-8 text-right">
                            <i class="far fa-times cursor-pointer hover:text-red-500"
                               @click="uppy.removeFile(file.id)" x-show="file.status == 'uploading'"></i>
                            <i class="far fa-redo cursor-pointer hover:text-red-500"
                               @click="uppy.retryUpload(file.id)" x-show="file.status == 'error'"></i>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
