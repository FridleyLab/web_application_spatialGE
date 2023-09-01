<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row justify-content-center">

            <div class="col-xl-10 col-sm-10 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_1</i>-->
<!--                        </div>-->
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">{{ project ? 'Update' : 'Create new' }} project</h6>
                        </div>
                    </div>

                    <div class="card-body">
                        <form role="form" :action="targetUrl" @submit.prevent="createProject" method="POST" autocomplete="off">

                            <input type="hidden" name="_token" :value="window._token">

                            <div class="mb-3 w-100 w-lg-50">
                                <div>What spatial transcriptomics platform are you using for this project?</div>
                                <div class="d-flex">
                                    <show-modal tag="new_project_platform"></show-modal>
                                    <select class="ms-2 form-select bg-white border border-1 p-2" required v-model="project_platform_id">
                                        <option value=""></option>
                                        <template v-for="platform in platforms">
                                            <option :value="platform.id" :selected="project !==null && project.project_platform_id === platform.id">{{ platform.name }}</option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex">
                                <show-modal tag="new_project_name"></show-modal>
                                <div class="ms-2 input-group input-group-outline w-100 w-md-50 w-lg-40 w-xl-30 mb-3" :class="(validName ? 'is-valid' : '') + (project ? ' focused is-focused' : '')">
                                    <label class="form-label">Project Name</label>
                                    <input required type="text" class="form-control" name="name" v-model="name" maxlength="100">
                                </div>
                            </div>

                            <div class="d-flex">
                                <show-modal tag="new_project_description"></show-modal>
                                <div class="ms-2 input-group input-group-outline mb-3" :class="(project ? 'focused is-focused' : '')">
                                    <label class="form-label">Description</label>
                                    <input type="text" class="form-control" name="description" v-model="description" maxlength="1000">
                                </div>
                            </div>

                            <show-message :message="errorMessage"></show-message>


                            <div class="row justify-content-center gap-2">
                                <button type="submit" class="btn btn-sm bg-gradient-info col-4 col-md-2 mt-4 mb-0">{{ project ? 'Update' : 'Create'}}</button>
                                <button type="button" class="btn btn-sm btn-outline-danger col-4 col-md-2 mt-4 mb-0" @click="window.location.href = '/projects'">Cancel</button>
                            </div>

                        </form>
                    </div>
                    <div class="card-footer text-center pt-0 px-lg-2 px-1">
                        <p class="mb-2 text-sm mx-auto">
                            After creating your project you can start importing data!
                        </p>
                    </div>
                </div>
            </div>


        </div>
    </div>
</template>
<script>
    export default {
        name: 'newProject',

        props: {
            targetUrl: String,
            project: {type: Object, default: null},
            platforms: {type: Object, default: null},
        },

        data() {
            return {
                project_platform_id: this.project ? this.project.project_platform_id : 0,
                name: this.project ? this.project.name : '',
                description: this.project ? this.project.description : '',

                errorMessage: '',

                //platforms: ['Visium', 'GeoMx', 'CosMx/SMI', 'MERFISH/MERSCOPE', 'Molecular Cartography', 'STARmap', 'Spatial Transcriptomics (Pre-Visium)', 'Generic'],
            }
        },

        mounted() {

        },

        computed: {
            validName() {
                return this.name.trim().length >= 4;
            }
        },

        methods: {
            createProject() {

                if(!this.validName)
                    this.errorMessage = 'Name has to be at least 4 characters long';
                else
                {
                    if(this.project) {
                        axios.patch(this.targetUrl, {name: this.name, description: this.description, project_platform_id: this.project_platform_id})
                            .then((response) => {
                                console.log(response.data);
                                location.href = response.data;
                            })
                            .catch((error) => {
                                console.log(error.message);
                                this.errorMessage = error.response.data;
                            });
                    }
                    else
                    {
                        axios.post(this.targetUrl, {name: this.name, description: this.description, project_platform_id: this.project_platform_id})
                            .then((response) => {
                                console.log(response.data);
                                location.href = response.data;
                            })
                            .catch((error) => {
                                console.log(error.message);
                                this.errorMessage = error.response.data;
                            });
                    }
                }


            }
        }
    }
</script>
