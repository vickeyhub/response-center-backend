<template>
    <div class="row g-4" v-if="Object.keys(appointment).length !== 0 && !recordNotFound">
        <div class="col-12">
            <div class="blueBox">
                <div class="row align-items-center">
                    <div class="col-12 col-md">
                        <div class="aInfo">
                            <h4>
                                {{
                                    $dayjs(appointment.date).format(
                                        "MMM DD, YYYY"
                                    )
                                }}
                                @ {{ appointment.time }}
                            </h4>
                            <h4 class="price">
                                <span v-if="appointment.payment_mode != 'Insurance'">Self-pay</span> Charge: ${{
                                    appointment.clinician.charges_per_session
                                }}
                            </h4>
                            <div class="Astatus pending mt-3">
                                Status: <span v-if="appointment.status == 'Booked'">Confirmed</span>
                                <span v-else>{{ appointment.status }}</span>
                            </div>
                            <button data-fancybox data-src="#status-dialog-content" title="Update Status"
                                alt="Update Status" class="btn btn-sm btn-warning Astatus ms-2"
                                v-if="$dayjs(appointment.date).isSame($dayjs(currentDate)) ||
                                    $dayjs(appointment.date).isAfter($dayjs(currentDate))"    
                                >
                                Change Status
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto pt-4 pt-lg-0" v-if="appointment.clinician">
                        <h6>Appointment with</h6>
                        <div class="cBox">
                            <div class="row g-3">
                                <div class="col-auto">
                                    <div class="cImg">
                                        <img :src="`${appointment.clinician.image}`" @error="noImage"
                                            v-if="appointment.clinician.image" />
                                        <img :src="noImg" v-else />
                                    </div>
                                </div>
                                <div class="col">
                                    <h3>
                                        {{ appointment.clinician.name }}
                                        <span>{{
                                            appointment.clinician.qualification
                                        }}</span>
                                    </h3>
                                    <div class="cInfo">
                                        {{ appointment.clinician.email }}<br />
                                        {{ appointment.clinician.phone_no }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12" v-if="appointment.referral">
            <div class="greyBox">
                <div class="row">
                    <div class="col-12 col-lg-6 d-flex">
                        <div>
                            <h4>Doctor's Details</h4>
                            <ul>
                                <li>
                                    <span>Name:</span>
                                    {{ appointment.referral.dr_name }}
                                </li>
                                <li>
                                    <span>Email:</span>
                                    {{ appointment.referral.dr_email }}
                                </li>
                                <li>
                                    <span>Mobile No.:</span>
                                    {{ appointment.referral.dr_mobile_number }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 d-flex">
                        <div>
                            <h4>Clinic's Details</h4>
                            <ul>
                                <li>
                                    <span>Name:</span>
                                    {{ appointment.referral.clinic_name }}
                                </li>
                                <li>
                                    <span>Email:</span>
                                    {{ appointment.referral.clinic_email }}
                                </li>
                                <li>
                                    <span>Phone No.:</span>
                                    {{
                                        appointment.referral
                                            .clinic_mobile_number
                                    }}
                                </li>
                                <li>
                                    <span>Address:</span>
                                    {{ appointment.referral.clinic_address }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <hr v-if="appointment.referral.reason" />
                    <div class="col-12" v-if="appointment.referral.reason">
                        <div>
                            <h4>Reason</h4>
                            {{ appointment.referral.reason }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6 d-flex">
            <div class="greyBox">
                <h4>Personal Details</h4>
                <ul>
                    <li>
                        <span>Name:</span> {{ appointment.first_name }}
                        {{ appointment.last_name }}
                    </li>
                    <li>
                        <span>Date of Birth:</span>
                        {{ $dayjs(appointment.dob).format("MMM DD, YYYY") }}
                    </li>
                    <li><span>Email:</span> {{ appointment.email }}</li>
                    <li>
                        <span>Mobile No.:</span> {{ appointment.home_phone }}
                    </li>
                    <li v-if="appointment.mobile_number">
                        <span>Alternate Phone:</span> {{ appointment.mobile_number }}
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12 col-lg-6 d-flex">
            <div class="greyBox">
                <h4>Payment Info</h4>
                <ul>
                    <li>
                        <span>Payment Mode:</span>
                        {{ appointment.payment_mode }}
                    </li>
                    <li v-if="appointment.insurance_id">
                        <span>Insurance:</span>
                        {{
                            appointment.insurance
                            ? appointment.insurance.name
                            : "N/A"
                        }}
                    </li>
                    <li v-if="appointment.insurance_plan_id">
                        <span>Insurance Plan:</span>
                        {{
                            appointment.plan ? appointment.plan.planname : "N/A"
                        }}
                    </li>
                    <li v-if="appointment.member_id">
                        <span>Member ID/EAP Auth:</span>
                        {{ appointment.member_id }}
                    </li>
                    <li v-if="appointment.policy_holder_gender">
                        <span>Policy Holder's Gender:</span>
                        {{ appointment.policy_holder_gender }}
                    </li>
                    <li v-if="appointment.service_provided">
                        <span>Service:</span>
                        {{ appointment.service_provided.name }}
                    </li>
                    <li v-if="appointment.relation_to_patient">
                        <span>Relation to Patient:</span>
                        {{ appointment.relation_to_patient }}
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12 px-4" v-if="appointment.cancellation_reason &&
            appointment.cancellation_reason != 'null'
            ">
            <h6>Reason for Cancelling Appointment:</h6>
            <p>{{ appointment.cancellation_reason }}</p>
        </div>
        
        <template v-if="appointment.payment_mode == 'Insurance'">

            <div class="col-12 col-lg-6 d-flex">
                <div class="greyBox">
                    <h4>Insurance Image (Front-Side)</h4>
                    <img :src="appointment.insurance_card_front" class="w-100" />
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex">
                <div class="greyBox">
                    <h4>Insurance Image (Back-Side)</h4>
                    <img :src="appointment.insurance_card_back" class="w-100" />
                </div>
            </div>

            <div class="col-12 col-lg-6 d-flex">
                <div class="greyBox">
                    <h4>Driver's License ID (Front-Side)</h4>
                    <img :src="appointment.driver_license_front" class="w-100" />
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex">
                <div class="greyBox">
                    <h4>Driver's License ID (Back-Side)</h4>
                    <img :src="appointment.driver_license_back" class="w-100" />
                </div>
            </div>
        </template>
    </div>
    <div class="row g-4" v-else>No record found</div>
    <div id="status-dialog-content" class="popup">
        <h2>Update Status</h2>

        <button data-fancybox-close="" @click="closeModal()" class="f-button is-close-btn" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" tabindex="-1">
                <path d="M20 20L4 4m16 0L4 20"></path>
            </svg>
        </button>

        <vee-form :validation-schema="schema" @submit="updateStatus" ref="cancelForm">
            <div class="row">
                <div class="col-12">
                    <div class="form-field">
                        <label for="">Select Status<span>*</span></label>
                        <vee-field v-slot="{ field, errors }" class="form-control" name="status" as="select"
                            v-model="updatedStatus">
                            <option value="">Select Status</option>
                            <option v-for="(status, index) in pendingStatuses" :key="index" :value="status.value" >
                                {{ status.text }}
                            </option>
                            <option v-for="(status, index) in confirmedStatuses" :key="index" :value="status.value" v-if="appointment.status == 'confirmed' ||
                                (type == 'referral' &&
                                    statusAppointment.status !=
                                    'Cancelled' &&
                                    statusAppointment.status !=
                                    'Pending' &&
                                    $dayjs(
                                        statusAppointment.date
                                    ).isAfter($dayjs(currentDate)))
                                ">
                                {{ status.text }}
                            </option>
                        </vee-field>
                        <div class="error invalid-feedback" v-if="statusErr">
                            <p class="invalid-feedback">
                                This field is required.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-auto">
                        <div class="submit-wrap">
                            <button
                                type="submit"
                                class="btn btn-primary-light btn-medium min-small"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    xmlns:xlink="http://www.w3.org/1999/xlink"
                                    style="margin: 0 10px"
                                    width="24px"
                                    height="24px"
                                    viewBox="0 0 100 100"
                                    preserveAspectRatio="xMidYMid"
                                    v-if="submittingForm"
                                >
                                    <circle
                                        cx="50"
                                        cy="50"
                                        r="32"
                                        stroke-width="8"
                                        stroke="#3A89C9"
                                        stroke-dasharray="50.26548245743669 50.26548245743669"
                                        fill="none"
                                        stroke-linecap="round"
                                    >
                                        <animateTransform
                                            attributeName="transform"
                                            type="rotate"
                                            repeatCount="indefinite"
                                            dur="1s"
                                            keyTimes="0;1"
                                            values="0 50 50;360 50 50"
                                        ></animateTransform>
                                    </circle>
                                </svg>
                                Update
                            </button>
                        </div>
                    </div>
            </div>

        </vee-form>


    </div>
</template>
<script>
// import { fetch } from "@/api/appointment";
import noImg from '/assets/images/no-img.jpg';
import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";
import {
    fetch,
    getSlots,
    rescheduleAppointment,
    updateAppointment,
} from "@/api/appointment";

const date = new Date();

let day = date.getDate();
let month = date.getMonth() + 1;
let year = date.getFullYear();
let currentDate = `${year}-${month}-${day}`;
export default {
    components: {},
    data() {
        return {
            appointment: {},
            recordNotFound: false,
            noImg,
            currentDate: currentDate,
            pendingStatuses: [
                {
                    type: "pending",
                    text: "Confirmed",
                    value: "Booked",
                },
                {
                    type: "",
                    text: "Cancel",
                    value: "Cancelled",
                },
            ],
            confirmedStatuses: [
                {
                    type: "",
                    text: "Cancel",
                    value: "cancelled",
                },
                {
                    text: "Fulfilled",
                    value: "fulfilled",
                },
                {
                    text: "Arrived",
                    value: "arrived",
                },
            ],
        };
    },
    methods: {
        noImage(event) {
            event.target.src = noImg;
        },
        getAppointment() {
            this.recordNotFound = false;
            const id = this.$route.params.appointmentId;
            fetch(id).then((response) => {
                if (response.success === true) {
                    this.appointment = response.data;
                } else {
                    this.recordNotFound = true;
                }
            });
        },
        
        updateStatus(values, { resetForm }) {
            // console.log(this.appointment.id);
            // return false;
            this.statusErr = false;
            if (this.updatedStatus == "") {
                this.statusErr = true;
            } else {
                let data = new FormData();
                this.submittingForm = true;
                data.append("appointment_id", this.appointment.id);
                data.append("cancellation_reason", null);
                data.append("status", this.updatedStatus);
                data.append("payment_mode", null);
                updateAppointment(data)
                    .then((response) => {
                        this.handleResponse(response);
                        this.submittingForm = false;
                        if (response.success == true) {
                            resetForm();
                            this.closeModal();
                            this.getAppointment();
                        }
                    })
                    .catch((error) => {
                        this.submittingForm = false;
                        this.handleCatch(error);
                    });
            }
        },

        closeModal() {
            // this.noSlots = false;
            // this.slots = [];
            // this.date = null;
            // this.appointmentId = null;
            // this.cancellationReason = null;
            // this.displayReason = false;
            // this.updatedStatus = "";
            // this.displayPaymentMode = false;
            // this.statusErr = false;
            Fancybox.close();
        },
    },
    mounted() {
        this.getAppointment();
    },
};
Fancybox.bind("[data-fancybox]", {
    closeButton: false,
    backdropClick: false,
});
</script>
<style scoped></style>
