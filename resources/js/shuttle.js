import Uppy from "@uppy/core"
import DropTarget from "@uppy/drop-target"
import Multipart from "@uppy/aws-s3-multipart"

window.Shuttle = (config) => ({
    config,
    context: {},

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
                this.uppy.log(err);
            }
        })

        event.target.value = null;
    },
})

window.Uppy = Uppy
window.UppyDropTarget = DropTarget
window.AwsS3Multipart = Multipart
