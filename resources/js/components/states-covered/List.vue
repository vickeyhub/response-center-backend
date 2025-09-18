<template>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th width="150" class="text-center">Status</th>
                    <!-- <th width="250">Action</th> -->
                </tr>
            </thead>
            <tbody>
                <tr v-for="(type, index) in statesCovered" :key="index">
                    <td>{{ type.name }}</td>
                    <td class="text-center">
                        <span class="d-inline-block"
                            @click="
                                updateStatus(type, type.status === 1 ? 0 : 1)
                            "
                            :class="
                                type.status == 1 ? 'status active' : 'status'
                            "
                        ></span>
                    </td>
                    <!-- <td>
                        <button class="btn btn-danger btn-sm" @click="deleteState(type.id, index)"><i class="bi bi-trash"></i></button>
                    </td> -->
                </tr>
            </tbody>
        </table>
    </div>
    <div class="row gy-4 align-items-center">
        <div class="col-auto"></div>
        <div class="col-auto ms-auto">
            <div class="row align-items-center justify-content-end">
                <div class="col-auto">Page</div>
                <div class="col ps-0">
                    <nav class="pagination-wrap">
                        <ul class="pagination flex-wrap pagination-sm mb-0">
                            <div v-for="pageNo in totalCount">
                                <li class="page-item">
                                    <btn
                                        @click="changePage(pageNo)"
                                        class="page-link"
                                        href="#"
                                        >{{ pageNo }}</btn
                                    >
                                </li>
                            </div>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { updateState, deleteState } from "@/api/states";
import { useToast } from "vue-toast-notification";
import "vue-toast-notification/dist/theme-sugar.css";
export default {
    props: {
        statesCovered: Array,
        totalCount: Number,
    },
    data() {
        return {};
    },
    methods: {
        changePage(pageNo) {
            this.$emit("changePage", (pageNo - 1) * 10);
        },
        updateStatus(data, status) {
            data.status = status;
            updateState(data, data.id)
                .then(() => {
                    this.$emit("changePage", 0);
                })
                .catch((error) => {
                    if (error.response?.data) {
                        console.log(response.message);
                    }
                });
        },

        deleteState(state_id, index){
            deleteState(state_id)
            .then((response) => {
                const $toast = useToast();
                $toast.open({
                    message: response.message,
                    type: "error",
                    position: "top-right",
                });
                this.statesCovered.splice(index, 1);
            })
            .catch((error) => {
                console.log(error);
            })
        }
    },
};
</script>
