<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row justify-content-center">

            <div class="col-xl-10 col-sm-10 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">format_list_numbered</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">My projects</h6>
                            <div>
                                <show-vignette url="/documentation/vignettes/my_projects.pdf"></show-vignette>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <div>
                            <div class="m-3 row">
                                <div class="text-2xl text-bolder col-4">Project name</div>
                                <div class="text-2xl text-bolder col-4">Description</div>
                                <div class="text-2xl text-bolder col-2 text-center">Created on</div>
                                <div class="text-2xl text-bolder text-center col-2">Actions</div>
                            </div>
                            <hr class="dark horizontal my-0">
                        </div>

                        <div v-for="project in projects">
                            <div class="m-3 row">
                                <div class="col-4"><a class="text-info text-bolder text-lg" :href="project.url">{{ project.name }}</a></div>
                                <div class="col-4">{{ project.description }}</div>
                                <div class="col-2 text-center">{{ project.created_on }}</div>
                                <div class="col-2 text-center">
                                    <i v-if="!deleting" class="material-icons opacity-10 text-info cursor-pointer ms-2" title="Edit" @click="editProject(project)">edit</i>
                                    <i v-if="!deleting" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deleting = project.id">delete</i>

                                    <input v-if="deleting === project.id" type="button" class="btn btn-sm btn-outline-success text-xxs" value="Cancel" @click="deleting = 0; deletingName = ''" title="Cancel deletion attempt" />
                                    <input v-if="deleting === project.id" type="text" class="w-100 text-xs border rounded rounded-2 border-danger" v-model="deletingName" placeholder="Type project name" title="Type in the project name to confirm and proceed">
                                    <div v-if="deleting === project.id" class="text-xs text-danger">All data will be deleted!</div>
                                    <input v-if="deleting === project.id && deletingName === project.name" type="button" class="btn btn-sm btn-outline-danger text-xxs" value="Delete" title="Confirm deletion of this sample" @click="deleteProject(project)" />
                                </div>
                            </div>
                            <hr class="dark horizontal my-0">
                        </div>
                        <div v-if="!projects.length">
                            You haven't created any projects!
                        </div>

                        <div class="text-end mt-3">
                            <a class=" btn text-white bg-gradient-info col-3" :href="newProjectUrl">
                                <span class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                                    <i class="material-icons opacity-10">create_new_folder</i>&nbsp;New Project
                                </span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>


        </div>
    </div>
</template>
<script>
export default {
    name: 'myProjects',

    props: {
        projects: Object,
        newProjectUrl: String,
    },

    data() {
        return {
            name: '',
            description: '',
            errorMessage: '',

            deleting: 0,
            deletingName: '',
        }
    },

    mounted() {
        console.log(this.projects);
    },

    computed: {
    },

    methods: {
        createProject() {

            if(!this.validName)
                this.errorMessage = 'Name has to be at least 4 characters long';
            else
            {
                axios.post(this.targetUrl, {name: this.name, description: this.description})
                    .then((response) => {
                        console.log(response.data);
                        location.href = response.data;
                    })
                    .catch((error) => {
                        console.log(error.message);
                        this.errorMessage = error.response.data;
                    });
            }


        },

        deleteProject(project) {
            axios.delete('/projects/' + project.id)
                .then((response) => {console.log(response.data); location.reload()})
                .catch((error) => {console.log(error.message)});
        },

        editProject(project) {
            location.href = '/projects/' + project.id + '/edit';
        }
    }
}
</script>
