@props(['trigger' => false, 'dropTarget' => 'body'])

<div class="drop-target absolute inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
    <div class="text-2xl xl:text-4xl text-white font-bold text-center">
        <i class="fas fa-cloud-upload fa-3x opacity-50 mb-3"></i>
        <div>Drop files to upload</div>
    </div>
</div>

<div x-title="shuttle"
    x-data="{
        context: @entangle('uploadContext'),

        uppy: null,
        state: 'IDLE',

        percent: 0,
        files: {},
        filesUploaded: 0,
        filesRemaining: function() {
            return this.uppy ? this.uppy.getFiles().length - this.filesUploaded : 0;
        },

        showDetails: false,

        setState: function(state) {
            this.state = state;
        },
        complete: function() {
            this.setState('COMPLETE');
            this.uppy.reset();
            this.percent = 0;
            this.files = {};
            this.filesUploaded = 0;

            setTimeout(() => {
                if(this.state == 'COMPLETE') {
                    this.setState('IDLE');
                }
            }, 3000);
        },
        unload: function unloadHandler (e) {
            if (this.state == 'UPLOADING') {
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave this page? Uploads in progress will be cancelled.';
            }
        },
        loadFiles: function (event) {
            Array.from(event.target.files).forEach((file) => {
                try {
                    this.uppy.addFile({
                        source: 'file input',
                        name: file.name,
                        type: file.type,
                        data: file,
                        meta: {}
                    })
                } catch (err) {
                    uppy.log(err);
                }
            })

            event.target.value = null;
        },
    }"
    x-init="() => {
        window.addEventListener('beforeunload', unload);
        uppy = Uppy({
            autoProceed: true,
            allowMultipleUploads: true,
            debug: true,
            onBeforeFileAdded: (file) => {
                file.meta = Object.assign(file.meta, context);
                file.meta.size = file.data.size;
            }
        }).use(UppyDropTarget, {
            target: document.querySelector('{{ $dropTarget }}')
        }).use(AwsS3Multipart, {
            limit: 300,
            companionUrl: '{{ Shuttle::baseUrl() }}',
        }).on('file-added', (file) => {
            setState('UPLOADING');
            files[file.id] = { id: file.id, name : file.name, size: file.size, progress: 0, status: 'uploading' };
        }).on('upload-progress', (file, progress) => {
            files[file.id].progress = Math.round(progress.bytesUploaded / progress.bytesTotal * 100);
        }).on('progress', (progress) => {
            percent = progress;
        }).on('upload-success', (file, response) => {
            filesUploaded++;
            files[file.id].status = 'complete';
            setTimeout(() => {
                delete files[file.id];
                @this.render();
            }, 500);
        }).on('upload-error', (file, error, response) => {
            files[file.id].status = 'error';
        }).on('file-removed', (file, reason) => {
            delete files[file.id];
        }).on('complete', (result) => {
            if(result.failed.length) {
                setState('COMPLETE_WITH_ERRORS');
            } else {
                complete();
            }
        });
    }"
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
