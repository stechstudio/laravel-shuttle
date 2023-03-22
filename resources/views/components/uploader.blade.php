@props([
    'trigger' => false,
    'dropTarget' => 'body',
    'config' => '{}',
    'debug' => false,
])

<div class="fixed h-screen drop-target absolute inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
    <div class="text-2xl xl:text-4xl text-white font-bold flex flex-col items-center">
        <x-shuttle::drop-icon viewBox="0 0 20 20" fill="currentColor" class="w-32 h-32 opacity-50 mb-3" />

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

<!--suppress ES6ShorthandObjectProperty, JSUnresolvedVariable, JSUnresolvedFunction, JSCheckFunctionSignatures -->
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.store("shuttle", {
            debug: false,

            uppy: null,

            state: "IDLE",

            overallProgress: 0,

            files: {},

            filesUploaded: 0,

            filesInProgress: 0,

            showDetails: false,

            get filesRemaining() {
                return Math.max(0, this.filesInProgress);
            },

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
                        file.meta = Object.assign(file.meta, config.context);
                        file.meta.size = file.data.size;
                    },
                });

                return this.uppy;
            },

            /**
             * Set the state.
             *
             * @param state
             */
            setState(state) {
                this.state = state;
            },

            /**
             * Set the number of files that are uploaded.
             *
             * @param filesUploaded
             */
            setFilesUploaded(filesUploaded) {
                this.filesUploaded = filesUploaded;
            },

            /**
             * Increment the number of files uploaded by 1.
             */
            incrementFilesUploadedCounter() {
                this.filesUploaded++;
            },

            /**
             * Decrement the number of files uploaded by 1.
             */
            decrementFilesUploadedCounter() {
                this.filesUploaded--;
            },

            /**
             * Set the overall progress.
             *
             * @param progress
             */
            setOverallProgress(progress) {
                this.overallProgress = progress;
            },

            /**
             * Increment the number of files in progress by 1.
             */
            incrementFilesInProgressCounter() {
                this.filesInProgress++;
            },

            /**
             * Decrement the number of files in progress by 1.
             */
            decrementFilesInProgressCounter() {
                this.filesInProgress--;
            },

            /**
             * Set the number of files in progress.
             */
            setFilesInProgress(filesInProgress) {
                this.filesInProgress = filesInProgress;
            },

            /**
             * Toggle the show details panel.
             *
             * @param show
             */
            toggleShowDetails(show) {
                this.showDetails = show;
            },

            /**
             * Reset the states of the uploader, uploads and the status bar.
             */
            reset() {
                // Let's double check to make sure there aren't no files currently being uploaded
                if (this.filesInProgress > 0) {
                    return;
                }

                this.state = "IDLE";

                this.uppy.reset();

                this.overallProgress = 0;

                this.files = {};

                this.filesUploaded = 0;

                this.filesInProgress = 0;
            },
        });

        Alpine.data("shuttle", () => ({
            newConfig: '{{ $config }}',

            config: {
                baseUrl: '{{ Shuttle::baseUrl() }}',

                context: @entangle('uploadContext'),

                dropTarget: '{{ $dropTarget }}',
            },

            init() {
                window.addEventListener("beforeunload", this.unload);

                Alpine.store("shuttle").createUppyInstance(this.config);

                this.loadUppyPlugins();

                this.addUppyEvents();
            },

            /**
             * Load the Uppy plugins.
             */
            loadUppyPlugins() {
                Alpine.store("shuttle").uppy
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
                Alpine.store("shuttle").uppy
                    .on("file-added", (file) => {
                        Livewire.emit("fileAdded", file);

                        Alpine.store("shuttle").setState("UPLOADING");
                        Alpine.store("shuttle").incrementFilesInProgressCounter();

                        Alpine.store("shuttle").files[file.id] = {
                            id: file.id,
                            name: file.name,
                            size: file.size,
                            progress: 0,
                            status: "uploading",
                            retries: 0,
                        };
                    })

                    .on("upload-progress", (file, progress) => {
                        Livewire.emit("connectionLost");

                        if (! this.checkInternetConnection()) {
                            return;
                        }

                        Livewire.emit("uploadProgress", file, progress);

                        Alpine.store("shuttle").files[file.id].progress = Math.round(progress.bytesUploaded / progress.bytesTotal * 100);
                        Alpine.store("shuttle").files[file.id].status = "uploading";

                        Alpine.store("shuttle").setState("UPLOADING");
                    })

                    .on("progress", (progress) => {
                        Livewire.emit("connectionLost");

                        if (! this.checkInternetConnection()) {
                            return;
                        }

                        Livewire.emit("progress", progress);

                        Alpine.store("shuttle").setOverallProgress(progress);
                    })

                    .on("upload-success", (file) => {
                        Livewire.emit("uploadSuccess", file);

                        Alpine.store("shuttle").incrementFilesUploadedCounter();
                        Alpine.store("shuttle").decrementFilesInProgressCounter();

                        Alpine.store("shuttle").files[file.id].status = "complete";

                    @this.
                    render();
                    })

                    .on("upload-error", (file) => {
                        Livewire.emit("uploadError", file);

                        Alpine.store("shuttle").setState("FAILED_WITH_ERRORS");
                        Alpine.store("shuttle").files[file.id].status = "error";

                        this.retryFileUpload(file);
                    })

                    .on("file-removed", (file) => {
                        Livewire.emit("fileRemoved", file);

                        delete Alpine.store("shuttle").files[file.id];

                        Alpine.store("shuttle").decrementFilesInProgressCounter();

                        if (Alpine.store("shuttle").uppy.getFiles().length === 0) {
                            this.abort();
                        }
                    })

                    .on("complete", (result) => {
                        Livewire.emit("complete", result);

                        if (result.failed.length) {
                            Alpine.store("shuttle").setState("COMPLETE_WITH_ERRORS");
                        }

                        if (Alpine.store("shuttle").filesRemaining === 0) {
                            this.complete();
                        }
                    });
            },

            /**
             * This method is fired once all file uploads are complete.
             */
            complete() {
                if (Alpine.store("shuttle").filesRemaining !== 0) {
                    return;
                }

                Alpine.store("shuttle").setState("COMPLETE");

                setTimeout(() => {
                    Alpine.store("shuttle").reset();
                }, 1000);
            },

            /**
             * Abort all upload and reset all state.
             */
            abort() {
                Alpine.store("shuttle").setState("IDLE");

                Alpine.store("shuttle").reset();
            },

            /**
             * Unload the file.
             *
             * @param e
             */
            unload(e) {
                if (Alpine.store("shuttle").state === "UPLOADING") {
                    e.preventDefault();

                    e.returnValue = '{{ trans(key: 'shuttle::shuttle.are_you_sure') }}';
                }
            },

            /**
             * Prepare loading the files for upload.
             *
             * @param event
             */
            loadFiles(event) {
                Array.from(event.target.files).forEach((file) => {
                    try {
                        Alpine.store("shuttle").uppy.addFile({
                            source: "file input",
                            name: file.name,
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

            /**
             * Check if the user is connected to the internet.
             *
             * @returns {boolean}
             */
            checkInternetConnection() {
                let connected = navigator.onLine;

                if (! connected) {
                    Alpine.store("shuttle").setState("CONNECTION_LOST");
                }

                return connected;
            },

            /**
             * Attempt to upload the file once more.
             *
             * @param file
             */
            retryFileUpload(file) {
                if (Alpine.store("shuttle").files[file.id].retries === 0) {
                    Alpine.store("shuttle").uppy.retryUpload(file.id).then();

                    Alpine.store("shuttle").files[file.id].retries = 1;
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
