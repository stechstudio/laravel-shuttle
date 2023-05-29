<script src="../../js/shuttle.js"></script>

@props([
    'trigger' => false,
    'dropTarget' => 'body',
    'config' => '{}',
    'debug' => false,
])

<div class="fixed h-screen drop-target absolute inset-0 z-50 bg-gray-500 bg-opacity-75 items-center justify-center">
    <div class="text-2xl xl:text-4xl text-white font-bold flex flex-col items-center">
        <x-shuttle::drop-icon viewBox="0 0 20 20" fill="currentColor" class="w-32 h-32 opacity-50 mb-3" />

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

        <x-shuttle::status-bar />
    </div>
</div>

<!--suppress ES6ShorthandObjectProperty, JSUnresolvedVariable, JSUnresolvedFunction, JSCheckFunctionSignatures -->
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("Shuttle", () => ({
            newConfig: '{{ $config }}',

            debug: false,

            uppy: null,

            state: "IDLE",

            showDetails: false,

            overallProgress: 0,

            files: {},

            filesUploaded: 0,

            filesInProgress: 0,

            config: {
                baseUrl: '{{ Shuttle::baseUrl() }}',

                context: @entangle('uploadContext'),

                dropTarget: '{{ $dropTarget }}',
            },

            init() {
                window.addEventListener("beforeunload", this.unload);

                this.createUppyInstance(this.config);
                this.loadUppyPlugins();

                if (! this.hasInternetConnection) {
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
                        try {
                            this.setState("UPLOADING");

                            this.incrementFilesInProgressCounter();

                            this.files[file.id] = {
                                id: file.id,
                                name: file.name,
                                size: file.size,
                                progress: 0,
                                status: "uploading",
                            };
                        } catch (error) {
                            this.abort();
                        }
                    })

                    .on("upload-progress", (file, progress) => {
                        this.files[file.id].progress = Math.round(progress.bytesUploaded / progress.bytesTotal * 100);
                    })

                    .on("progress", (progress) => {
                        this.setOverallProgress(progress);
                    })

                    .on("upload-success", (file) => {
                        this.files[file.id].status = "complete";

                        this.incrementFilesUploadedCounter();
                        this.decrementFilesInProgressCounter();

                    @this.render();
                    })

                    .on("upload-error", (file) => {
                        this.uppy.retryUpload(file.id).then();
                    })

                    .on("file-removed", (file) => {
                        delete this.files[file.id];

                        // let's check if that was the last remaining
                        // file, if it was, let's complete the
                        // process by  calling abort()
                        if (this.filesRemaining === 0) {
                            this.abort();
                        }
                    })

                    .on("complete", (result) => {
                        if (this.filesInProgress === 0) {
                            this.abort();
                        }
                    });
            },

            /**
             * Unload the file.
             *
             * @param e
             */
            unload(e) {
                e.preventDefault();

                e.returnValue = '{{ trans(key: 'shuttle::shuttle.are_you_sure') }}';
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
                    this.abort();
                }
            },

            /**
             * Abort all uploads.
             */
            abort() {
                this.setState("IDLE");

                this.setShowDetails(false);

                this.uppy.reset();
            },

            get filesRemaining() {
                return Math.max(0, this.filesInProgress);
            },

            get hasInternetConnection() {
                let connected = navigator.onLine;

                return connected;
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
             * Set state.
             *
             * @param value
             */
            setState(value) {
                this.state = value;
            },

            /**
             * Set show details.
             *
             * @param value
             */
            setShowDetails(value) {
                this.showDetails = value;
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
