<div class="bg-white divide-y divide-gray-200 text-sm xl:text-base text-gray-700 max-h-48 overflow-y-auto">
    <template x-for="[id, file] of Object.entries(files)" :key="id">
        <div class="flex justify-between items-center px-4 py-3" x-show="showDetails || file.status === 'error'">
            <div class="flex items-center">
                <svg x-show="file.status === 'uploading'" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="w-6 h-6" style="margin: auto; background: none; display: block; shape-rendering: auto;">
                    <circle cx="50" cy="50" r="32" stroke-width="8" stroke="#85a2b6" stroke-dasharray="50.26548245743669 50.26548245743669" fill="none" stroke-linecap="round">
                        <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" keyTimes="0;1" values="0 50 50;360 50 50"></animateTransform>
                    </circle>
                </svg>

                <svg x-show="file.status === 'complete'" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 text-green-500">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>

                <svg x-show="file.status === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 text-red-500">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>

                <div x-text="file.name" class="text-gray-700 ml-2"></div>
            </div>

            <div class="flex items-center">
                <div x-text="file.progress"></div>
                %

                <div class="w-8 text-right">
                    <svg @click="uppy.removeFile(file.id)" x-show="file.status === 'uploading'" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 cursor-pointer hover:text-red-500">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>

                    <svg @click="uppy.retryUpload(file.id)" x-show="file.status === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="w-6 h-6 cursor-pointer hover:text-red-500">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </template>
</div>
