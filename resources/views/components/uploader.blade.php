@props([
    'trigger' => false,
    'dropTarget' => 'body',
    'config' => '{}',
    'debug' => false,
])

<div class="fixed h-screen drop-target absolute inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
    <div class="text-2xl xl:text-4xl text-white font-bold flex flex-col items-center">
        <svg viewBox="0 0 20 20" fill="currentColor" class="w-32 h-32 opacity-50 mb-3">
            <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
            <path d="M9 13h2v5a1 1 0 11-2 0v-5z"></path>
        </svg>

        <div>{{ trans(key: 'shuttle::shuttle.drop_files') }}</div>
    </div>
</div>

<!--suppress JSUnresolvedVariable -->
<div
    x-title="shuttle"
    x-data="shuttle"
    x-on:select-files.window="document.querySelector('.uppy-trigger').click(); if ('activeElement' in document) document.activeElement.blur();"
>
    <div wire:ignore class="absolute inset-x-0 bottom-0 z-50">
        <!--suppress JSUnresolvedFunction -->
        <input x-on:change="loadFiles($event)" type="file" class="hidden uppy-trigger" name="files[]" multiple>

        <x-shuttle::status-bar />
    </div>
</div>

<!--suppress ES6ShorthandObjectProperty, JSUnresolvedVariable, JSUnresolvedFunction -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('shuttle', () => ({
            newConfig: '{{ $config }}',

            config: {
                baseUrl: '{{ Shuttle::baseUrl() }}',

                context: @entangle('uploadContext'),

                dropTarget: '{{ $dropTarget }}',
            },

            debug: '{{ $debug }}',

            uppy: null,

            state: 'IDLE',

            percent: 0,

            files: {},

            filesUploaded: 0,

            filesInProgress: 0,

            showDetails: false,

            init() {
                window.addEventListener('beforeunload', this.unload);

                this.createUppyInstance();
            },

            createUppyInstance() {
                this.uppy = new Uppy({
                    autoProceed: true,
                    allowMultipleUploads: true,
                    debug: this.debug,
                    onBeforeFileAdded: (file) => {
                        file.meta = Object.assign(file.meta, this.config.context);
                        file.meta.size = file.data.size;
                        file.meta.attempts = 0;
                    },
                });

                this.loadUppyPlugins();

                this.addUppyEvents();
            },

            loadUppyPlugins() {
                this.uppy
                    .use(UppyDropTarget, {
                        target: document.querySelector(this.config.dropTarget)
                    })
                    .use(AwsS3Multipart, {
                        limit: 300,
                        companionUrl: this.config.baseUrl,
                        companionHeaders: {
                            'X-CSRF-Token': 'xxx',
                        }
                    });
            },

            addUppyEvents() {
                this.uppy
                    .on('file-added', (file) => {
                        this.setState('UPLOADING');

                        Livewire.emit('fileAdded', file);

                        this.filesInProgress++;

                        this.files[file.id] = {
                            id: file.id,
                            name: file.name,
                            size: file.size,
                            progress: 0,
                            status: 'uploading',
                            retries: 0,
                        };
                    })

                    .on('upload-progress', (file, progress) => {
                        if (! this.checkInternetConnection()) {
                            return;
                        }

                        Livewire.emit('uploadProgress', file, progress);

                        this.files[file.id].progress = Math.round(progress.bytesUploaded / progress.bytesTotal * 100);

                        this.files[file.id].status = 'uploading';

                        this.setState('UPLOADING');
                    })

                    .on('progress', (progress) => {
                        if (! this.checkInternetConnection()) {
                            return;
                        }

                        Livewire.emit('progress', progress);

                        this.percent = progress;

                        this.setState('UPLOADING');
                    })

                    .on('upload-success', (file) => {
                        Livewire.emit('uploadSuccess', file);

                        this.filesUploaded++;
                        this.filesInProgress--;

                        this.files[file.id].status = 'complete';

                        setTimeout(() => {
                            @this.
                            render();
                        }, 500);
                    })

                    .on('upload-error', (file) => {
                        Livewire.emit('uploadError', file);

                        this.files[file.id].status = 'error';

                        if (this.files[file.id].retries === 0) {
                            // retry once...
                            this.uppy.retryUpload(file.id).then();

                            this.files[file.id].retries = 1;
                        }
                    })

                    .on('file-removed', (file) => {
                        Livewire.emit('fileRemoved', file);

                        delete this.files[file.id];

                        if (this.uppy.getFiles().length === 0) {
                            this.abort();
                        }
                    })

                    .on('complete', (result) => {
                        Livewire.emit('complete', result);

                        this.filesInProgress--;

                        if (result.failed.length) {
                            this.setState('COMPLETE_WITH_ERRORS');
                        }

                        if (this.filesRemaining === 0) {
                            this.complete();
                        }
                    });
            },

            get filesRemaining() {
                return Math.max(0, this.filesInProgress);
            },

            setState(state) {
                this.state = state;
            },

            reset() {
                this.uppy.reset();

                this.percent = 0;

                this.files = {};

                this.filesUploaded = 0;

                this.filesInProgress = 0;
            },

            complete() {
                setTimeout(() => {
                    this.setState('COMPLETE');
                }, 1000);

                setTimeout(() => {
                    if (this.filesRemaining === 0) {
                        this.setState('IDLE');

                        this.reset();
                    }
                }, 2000);
            },

            abort() {
                this.setState('IDLE');

                this.reset();
            },

            unload(e) {
                if (this.state === 'UPLOADING') {
                    e.preventDefault();

                    e.returnValue = '{{ trans(key: 'shuttle::shuttle.are_you_sure') }}';
                }
            },

            loadFiles(event) {
                Array.from(event.target.files).forEach((file) => {
                    try {
                        this.uppy.addFile({
                            source: 'file input',
                            name: file.name + rand(),
                            type: file.type,
                            data: file,
                            meta: {},
                        });
                    } catch (err) {
                        uppy.log(err);
                    }
                });

                event.target.value = null;
            },

            checkInternetConnection() {
                let connected = navigator.onLine;

                // check if the user is connected to the internet
                if (! connected) {
                    this.setState('CONNECTION_LOST');
                }

                return connected;
            }
        }));
    });
</script>

<style>
    .drop-target {
        display: none;
        height: 100vh;
        box-sizing: border-box;
        position: fixed;
        width: 100%;
        left: 0;
        top: 0;
        z-index: 99999;
    }

    body.uppy-is-drag-over .drop-target {
        display: flex;
        height: 100vh;
    }
</style>
