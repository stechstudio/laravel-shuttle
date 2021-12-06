import Uppy from "@uppy/core"
import DropTarget from "@uppy/drop-target"
import Multipart from "@uppy/aws-s3-multipart"

const Shuttle = (config) => ({
    context: config.uploadContext,

    uppy: null,
    state: 'IDLE',

    percent: 0,

    files: {},
    filesUploaded: 0,
    filesRemaining: function () {
        return this.uppy ? this.uppy.getFiles().length - this.filesUploaded : 0
    },

    showDetails: false,

    setState: function (state) {
        this.state = state
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

    unload: function (e) {
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
                this.uppy.log(err);
            }
        })

        event.target.value = null;
    },

    init() {
        console.log(config)

        window.addEventListener('beforeunload', (e) => {
            this.unload(e)
        });

        this.uppy = new Uppy({
            autoProceed: true,
            allowMultipleUploads: true,
            debug: true,
            onBeforeFileAdded: (file) => {
                file.meta = Object.assign(file.meta, context);
                file.meta.size = file.data.size;
            }
        }).use(DropTarget, {
            target: document.querySelector(config.dropTarget),
        }).use(Multipart, {
            limit: 300,
            companionUrl: config.baseUrl,
        }).on('file-added', (file) => {
            this.setState('UPLOADING');

            this.files[file.id] = { id: file.id, name : file.name, size: file.size, progress: 0, status: 'uploading' };
        }).on('upload-progress', (file, progress) => {
            this.files[file.id].progress = Math.round(progress.bytesUploaded / progress.bytesTotal * 100);
        }).on('progress', (progress) => {
            this.percent = progress;
        }).on('upload-success', (file, response) => {
            this.filesUploaded++;

            this.files[file.id].status = 'complete';

            setTimeout(() => {
                delete this.files[file.id];

                this.$wire.render()
            }, 500);
        }).on('upload-error', (file, error, response) => {
            this.files[file.id].status = 'error';
        }).on('file-removed', (file, reason) => {
            delete this.files[file.id];
        }).on('complete', (result) => {
            if (result.failed.length) {
                this.setState('COMPLETE_WITH_ERRORS');
            } else {
                this.complete();
            }
        });
    }
})

window.Shuttle = Shuttle

export default Shuttle
