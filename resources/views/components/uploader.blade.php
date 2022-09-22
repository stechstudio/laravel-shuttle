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

        <div>{{ trans(key: 'drop_files') }}</div>
    </div>
</div>

<div
    x-title="shuttle"
    x-data="shuttle"
    x-on:select-files.window="document.querySelector('.uppy-trigger').click(); if ('activeElement' in document) document.activeElement.blur();"
>
    <div wire:ignore class="absolute inset-x-0 bottom-0 z-50">
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
                this.debugIf(this.debug, () => {
                    console.log('calling init');
                });

                window.addEventListener('beforeunload', this.unload);

                this.createUppyInstance();
            },

            createUppyInstance() {
                this.debugIf(this.debug, () => {
                    console.log('calling createUppyInstance');
                });

                this.uppy = new Uppy({
                    autoProceed: true,
                    allowMultipleUploads: true,
                    debug: this.debug,
                    onBeforeFileAdded: (file) => {
                        file.meta = Object.assign(file.meta, this.config.context);
                        file.meta.size = file.data.size;
                    },
                });

                this.loadUppyPlugins();

                this.addUppyEvents();
            },

            loadUppyPlugins() {
                this.debugIf(this.debug, () => {
                    console.log('calling loadUppyPlugins');
                });

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
                this.debugIf(this.debug, () => {
                    console.log('calling addUppyEvents');
                });

                this.uppy
                    .on('file-added', (file) => {
                        this.setState('UPLOADING');

                        Livewire.emit('fileAdded', file);

                        this.filesInProgress++;

                        this.files[file.id] = { id: file.id, name: file.name, size: file.size, progress: 0, status: 'uploading' };
                    })

                    .on('upload-progress', (file, progress) => {
                        this.debugIf(this.debug, () => {
                            console.log('on upload-progress');
                            console.log('file', file);
                            console.log('progress', progress);
                        });

                        Livewire.emit('uploadProgress', file, progress);

                        this.files[file.id].progress = Math.round(progress.bytesUploaded / progress.bytesTotal * 100);

                        this.files[file.id].status = 'uploading';

                        this.debugIf(this.debug, () => {
                            console.log('setting state to UPLOADING');
                        });

                        this.setState('UPLOADING');
                    })

                    .on('progress', (progress) => {
                        this.debugIf(this.debug, () => {
                            console.log('on progress');
                            console.log('progress', progress);
                            console.log('emitting progress Livewire event');
                        });

                        Livewire.emit('progress', progress);

                        this.percent = progress;

                        this.debugIf(this.debug, () => {
                            console.log('setting state to UPLOADING');
                        });

                        this.setState('UPLOADING');
                    })

                    .on('upload-success', (file) => {
                        this.debugIf(this.debug, () => {
                            console.log('on upload-success');
                            console.log('emitting uploadSuccess Livewire event');
                            console.log('file', file);
                            console.log('incrementing filesUploaded', filesUploaded);
                        });

                        Livewire.emit('uploadSuccess', file);

                        this.filesUploaded++;

                        this.files[file.id].status = 'complete';

                        this.debugIf(this.debug, () => {
                            console.log('setting status to complete');
                            console.log('forcing a rerender');
                        });

                        setTimeout(() => {
                            @this.render();
                        }, 500);
                    })

                    .on('upload-error', (file) => {
                        this.debugIf(this.debug, () => {
                            console.log('on upload-error');
                            console.log('emitting uploadError Livewire event');
                            console.log('file', file);
                        });

                        Livewire.emit('uploadError', file);

                        this.files[file.id].status = 'error';

                        this.debugIf(this.debug, () => {
                            console.log('setting status to error');
                            console.log('setting state to CONNECTION_LOST');
                        });

                        this.setState('CONNECTION_LOST');
                    })

                    .on('file-removed', (file) => {
                        this.debugIf(this.debug, () => {
                            console.log('on file-removed');
                            console.log('emitting fileRemoved Livewire event');
                            console.log('file', file);
                        });

                        Livewire.emit('fileRemoved', file);

                        this.filesInProgress--;

                        delete this.files[file.id];

                        this.debugIf(this.debug, () => {
                            console.log('removing the file');
                            console.log('file', file);
                            console.log('aborting');
                        });

                        if (this.uppy.getFiles().length === 0) {
                            this.abort();
                        }
                    })

                    .on('complete', (result) => {
                        this.debugIf(this.debug, () => {
                            console.log('on complete');
                            console.log('emitting complete Livewire event');
                            console.log('result', result);
                        });

                        Livewire.emit('complete', result);

                        this.filesInProgress--;

                        if (result.failed.length) {
                            this.debugIf(this.debug, () => {
                                console.log('setting state to COMPLETE_WITH_ERRORS');
                            });

                            this.setState('COMPLETE_WITH_ERRORS');
                        } else {
                            this.debugIf(this.debug, () => {
                                console.log('calling complete method');
                            });

                            this.complete();
                        }
                    });
            },

            filesRemaining() {
                this.debugIf(this.debug, () => {
                    console.log('filesRemaining', this.filesInProgress);
                });

                return this.filesInProgress;
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

                this.debugIf(this.debug, () => {
                    console.log('reset');
                    console.log('calling uppy reset method');
                    console.log('setting percent to 0', this.percent);
                    console.log('setting files array to []', this.files);
                    console.log('setting files uploaded counter to 0', this.filesUploaded);
                    console.log('setting files in progress counter to 0', this.filesInProgress);
                });
            },

            complete() {
                this.setState('COMPLETE');

                setTimeout(() => {
                    if (this.state === 'COMPLETE')
                        this.debugIf(this.debug, () => {
                            console.log('COMPLETE');
                            console.log('setting state to IDLE');
                            console.log('calling reset method');
                        });
                    {
                        this.setState('IDLE');

                        this.reset();
                    }
                }, 3000);
            },

            abort() {
                this.setState('IDLE');

                this.reset();
            },

            unload(e) {
                if (this.state === 'UPLOADING') {
                    e.preventDefault();

                    e.returnValue = 'Are you sure you want to leave this page? Uploads in progress will be cancelled.';
                }
            },

            loadFiles(event) {
                Array.from(event.target.files).forEach((file) => {
                    try {
                        this.uppy.addFile({
                            source: 'file input',
                            name: file.name,
                            type: file.type,
                            data: file,
                            meta: {}
                        });
                    } catch (err) {
                        uppy.log(err);
                    }
                });

                event.target.value = null;
            },

            debugIf(condition, callback) {
                if (condition) {
                    return callback;
                }
            },
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
