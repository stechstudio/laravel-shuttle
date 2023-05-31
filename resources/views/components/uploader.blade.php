@props([
    'trigger' => false,
    'dropTarget' => 'body',
    'config' => '{}',
    'debug' => false,
])

<div class="fixed h-screen drop-target absolute inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
    <div class="text-2xl xl:text-4xl text-white font-bold flex flex-col items-center">
        <x-shuttle::drop-icon viewBox="0 0 20 20" fill="currentColor" class="w-32 h-32 opacity-50 mb-3"/>

        <div>@lang('shuttle::shuttle.drop_files')</div>
    </div>
</div>

<!--suppress JSUnresolvedVariable -->
<div
        x-data="Shuttle"
        x-on:select-files.window="document.querySelector('.uppy-trigger').click(); if ('activeElement' in document) document.activeElement.blur();"
>
    <div wire:ignore class="absolute inset-x-0 bottom-0 z-50">
        <!--suppress JSUnresolvedFunction -->
        <input x-on:change="loadFiles($event)" type="file" class="hidden uppy-trigger" name="files[]" multiple>

        <x-shuttle::status-bar/>
    </div>
</div>

<!--suppress ES6ShorthandObjectProperty, JSUnresolvedVariable, JSUnresolvedFunction, JSCheckFunctionSignatures -->
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("Shuttle", () => ({
                newConfig: '{{ $config }}',

                debug: true, // @todo: change to false

                uppy: null,

                state: "IDLE",

                showDetails: false,

                overallProgress: 0,

                files: {},

                filesUploaded: 0,

                filesInProgress: 0,

                success: false,

                config: {
                    baseUrl: '{{ Shuttle::baseUrl() }}',

                    context: @entangle('uploadContext'),

                    dropTarget: '{{ $dropTarget }}',
                },

                init() {
                    window.addEventListener("beforeunload", this.unload);

                    this.createUppyInstance(this.config);
                    this.loadUppyPlugins();

                    if (!this.hasInternetConnection) {
                        this.setState('CONNECTION_LOST');

                        return;
                    }

                    this.addUppyEvents();
                },

                /**
                 * Load the Uppy plugins.
                 */
                loadUppyPlugins() {
                    this.uppy
                        .use(UppyDropTarget, {
                            target: document.querySelector(this.config.dropTarget),
                        })
                        .use(AwsS3Multipart, {
                            limit: 300,
                            companionUrl: this.config.baseUrl,
                            companionHeaders: {
                                "X-CSRF-Token": "xxx",
                            },
                        });
                },

                /**
                 * Add the Uppy events.
                 */
                addUppyEvents() {
                    this.uppy
                        .on("file-added", (file) => {
                            this.setState("UPLOADING");

                            this.files[file.id] = {
                                id: file.id,
                                name: file.name,
                                size: file.size,
                                progress: 0,
                            };
                        })

                        .on('upload', (data) => {
                            //
                        })

                        .on("upload-progress", (file, progress) => {
                            //
                        })

                        .on("progress", (progress) => {
                            //
                        })

                        .on("upload-success", (file) => {
                            delete this.files[file.id];

                            this.recalculateState();

                            setTimeout(() => {
                                this.uppy.removeFile(file.id);
                            }, 500);
                        })

                        .on("upload-error", (file) => {
                            this.uppy.retryUpload(file.id).then();
                        })

                        .on("complete", (result) => {
                            this.setState('IDLE');

                            this.uppy.reset();

                            this.success = true;
                            console.log('changed success to ' + this.success)

                            setTimeout(() => {
                                this.success = false;
                                console.log('timeout, changed success to ' + this.success)
                            }, 1000)
                        });
                },

                /**
                 * Prepare loading the files for upload.
                 *
                 * @param event
                 */
                loadFiles(event) {
                    try {
                        Array.from(event.target.files).forEach((file) => {
                            this.uppy.addFile({
                                source: "file input",
                                name: file.name,
                                type: file.type,
                                data: file,
                                meta: {},
                            });
                        });

                        event.target.value = null;
                    } catch (error) {
                        //
                    }
                }
                ,

                /**
                 * Unload the file.
                 *
                 * @param e
                 */
                unload(e) {
                    if (this.state === "UPLOADING") {
                        e.preventDefault();

                        e.returnValue = '{{ trans(key: 'shuttle::shuttle.are_you_sure') }}';
                    }
                }
                ,

                /**
                 * Abort all uploads.
                 */
                abort() {
                    this.setState("IDLE");
                    this.setShowDetails(false);

                    this.uppy.reset();
                }
                ,

                get hasInternetConnection() {
                    let connected = navigator.onLine;

                    return connected;
                }
                ,

                /**
                 * Create a Uppy instance.
                 *
                 * @param config
                 * @returns {null}
                 */
                createUppyInstance(config) {
                    this.uppy = new Uppy({
                        autoProceed: true,
                        allowMultipleUploads: true,
                        debug: this.debug,
                        onBeforeFileAdded: (file) => {
                            try {
                                file.meta = Object.assign(file.meta, config.context);
                                file.meta.size = file.data.size;
                            } catch (error) {
                                console.log('onBeforeFileAdded')
                            }
                        },
                    });

                    return this.uppy;
                },

                /**
                 * Set state.
                 *
                 * @param value
                 */
                setState(value) {
                    this.state = value;
                },

                recalculateState() {
                    for (const file of this.uppy.getFiles()) {
                        if (file.error || file.progress.bytesUploaded < file.progress.bytesTotal) {
                            this.setState('UPLOADING');

                            return;
                        }
                    }

                    this.setState('IDLE')
                },
            })
        );
    })
    ;
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
