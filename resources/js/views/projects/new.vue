<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row justify-content-center">

            <div class="col-xl-10 col-sm-10 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_1</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">Create new project</h6>
                        </div>
                    </div>

                    <div class="card-body">
                        <form role="form" :action="targetUrl" @submit.prevent="createProject" method="POST">

                            <input type="hidden" name="_token" :value="window._token">

                            <div class="input-group input-group-outline mb-3" :class="validName ? 'is-valid' : ''">
                                <label class="form-label">Project Name</label>
                                <input required type="text" class="form-control" name="name" v-model="name">
                            </div>

                            <div class="input-group input-group-outline mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" v-model="description">
                            </div>

                            <show-message :message="errorMessage"></show-message>

                            <div class="text-center">
                                <button type="submit" class="btn btn-lg bg-gradient-info btn-lg w-25 mt-4 mb-0">Create</button>
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
        },

        data() {
            return {
                name: '',
                description: '',
                errorMessage: ''
            }
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


            }
        }
    }
</script>
