@extends('layouts.app')

@section('content')
    <div id="repositoryAutoCreation" v-cloak>
        <div class="container mt-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0" style="color: #FFFFFF">Repository Auto Creation</h5>
                </div>
                <div class="card-body">
                    <form @submit.prevent="submitForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tokenSelect" class="form-label mt-2">Token</label>
                                <select class="form-select" v-model="selectedToken" :style="getValidationStyle('token_id')">
                                    <option value="">Select Token</option>
                                    <option v-for="token in tokens" :key="token.id" :value="token.id">@{{ token.login_name }}</option>
                                </select>
                                <div v-if="getValidationError('token_id')" class="text-danger mt-2">@{{ getValidationError('token_id') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="organizationInput" class="form-label mt-2">Organization</label>
                                <input v-model="organization" type="text" id="organizationInput" class="form-control" placeholder="Enter Organization Name" :style="getValidationStyle('organization')"/>
                                <div v-if="getValidationError('organization')" class="text-danger mt-2">@{{ getValidationError('organization') }}</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="groupCountInput" class="form-label mt-2">Number Of Groups</label>
                                <input min="0" type="number" v-model.number="numberOfGroups" :style="getValidationStyle('group_count')" id="groupCountInput" class="form-control" placeholder="Enter Number Of Groups" @change="generateMembers" />
                                <div v-if="getValidationError('group_count')" class="text-danger mt-2">@{{ getValidationError('group_count') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="membersPerGroupInput" class="form-label mt-2">Number of Members per Group</label>
                                <input min="0" type="number" v-model.number="numberOfCollaboratorsPerGroup" :style="getValidationStyle('members_per_group')" id="membersPerGroupInput" class="form-control" placeholder="Enter Number of Members per Group" @change="generateMembers" />
                                <div v-if="getValidationError('members_per_group')" class="text-danger mt-2">@{{ getValidationError('members_per_group') }}</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-2">
                                <label for="updateDeadline" class="form-label">Deadline</label>
                                <input v-model="deadline" type="date" id="updateDeadline" :min="minDate" :style="getValidationStyle('deadline')" class="form-control" />
                                <div v-if="getValidationError('deadline')" class="text-danger">@{{ getValidationError('deadline') }}</div>
                            </div>
                        </div>
                        <div class="divider divider-primary" v-if="numberOfGroups !== 0">
                            <div class="divider-text">Groups Info</div>
                        </div>
                        <div v-for="groupIndex in numberOfGroups" :key="groupIndex" class="mb-4">
                            <div class="card mb-3">
                                <div class="card-header text-white align-content-center" style="background-color: #28154b">
                                    <h5 class="mb-0 align-content-center" style="color:#FFFFFF ">Group @{{ groupIndex }}</h5>
                                </div>
                                <div class="card-body">
                                    <div v-for="memberIndex in numberOfCollaboratorsPerGroup" :key="memberIndex" class="mb-3">
                                        <span class="badge mt-2" style="background-color: #980c4d">@{{ ordinal(memberIndex) }} Member</span>
                                        <div class="row">
                                            <div class="col-md-6 mt-2">
                                                <label class="form-label">GitHub Username</label>
                                                <input type="text" class="form-control" v-model="members[groupIndex - 1][memberIndex - 1].github_username" placeholder="GitHub Username">
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label class="form-label">University Username</label>
                                                <input type="text" class="form-control" v-model="members[groupIndex - 1][memberIndex - 1].university_username" placeholder="University Username">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="getValidationStyle('members')" class="alert alert-danger" role="alert">@{{ getValidationError('members') }}</div>
                            <div v-if="organizationPermissionFailed !== ''" class="alert alert-danger" role="alert">@{{organizationPermissionFailed}}</div>
                            <div v-if="githubUsernameFailed !== ''" class="alert alert-danger" role="alert">@{{githubUsernameFailed}}</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let repositoryAutoCreation = new Vue({
                el: '#repositoryAutoCreation',
                data: {
                    tokens: [],
                    selectedToken: '',
                    organization: '',
                    numberOfGroups: 0,
                    minDate: '',
                    numberOfCollaboratorsPerGroup: 0,
                    members: [],
                    validationErrors: [],
                    organizationPermissionFailed:'',
                    githubUsernameFailed:'',
                    deadline: '',
                },
                methods: {
                    fetchTokens() {
                        let url = '{{ route('token.fetch') }}';
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                this.tokens = data.data;
                            })
                            .catch(error => {
                                console.error('Error fetching tokens:', error);
                            });
                    },
                    generateMembers() {
                        self = this;
                        this.members = Array.from({ length: self.numberOfGroups }, () =>
                            Array.from({ length: self.numberOfCollaboratorsPerGroup }, () =>
                                ({ github_username: '', university_username: '' })
                            )
                        );
                    },
                    getValidationError(field) {
                        self = this
                        const error = self.validationErrors.find(err => err.field === field);
                        return error ? error.message : null;
                    },
                    getValidationStyle(field) {
                        return this.getValidationError(field) ? 'border-color: red;' : '';
                    },
                    setDateLimits() {
                        self = this
                        const today = new Date();
                        const tomorrow = new Date(today);
                        tomorrow.setDate(tomorrow.getDate() + 2);
                        self.minDate = tomorrow.toISOString().split('T')[0];
                        self.deadline = self.minDate;
                    },
                    submitForm() {
                        self = this
                        const payload = {
                            token_id: self.selectedToken,
                            organization: self.organization,
                            group_count: self.numberOfGroups,
                            members_per_group: self.numberOfCollaboratorsPerGroup,
                            members: self.members.flat(),
                            deadline: self.deadline
                        };
                        fetch('{{ route('repository.auto-create') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        })
                            .then(response =>{
                                if (!response.ok) {
                                    if (response.status === 422) {
                                        return response.json().then(err => {
                                            self.validationErrors = err.errors;
                                            throw new Error('Validation failed');
                                        });
                                    }
                                    if (response.status === 403) {
                                        return response.json().then(err => {
                                            self.organizationPermissionFailed = err.message;
                                            throw new Error('Organization permission failed');
                                        });
                                    }
                                    if (response.status === 400) {
                                        return response.json().then(err => {
                                            self.githubUsernameFailed= err.message;
                                            throw new Error('Github username validation failed');
                                        });
                                    }
                                    throw new Error('Response was not ok!')
                                }
                                return response.json();
                            })
                            .then(data => {
                                // Assuming the successful response contains a URL or route name for redirection
                                window.location.href = '{{ route('repository.repository-list-view') }}';
                            })
                            .catch(error => {
                                console.error('Error creating repositories:', error);
                            });
                    },
                    ordinal(n) {
                        const s = ["th", "st", "nd", "rd"],
                            v = n % 100;
                        return n + (s[(v - 20) % 10] || s[v] || s[0]);
                    }
                },
                watch: {
                    numberOfGroups(newVal, oldVal) {
                        self = this;
                        if (newVal !== oldVal) {
                            self.generateMembers();
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'group_count');
                            self.githubUsernameFailed = '';
                            self.organizationPermissionFailed = '';
                        }
                    },
                    numberOfCollaboratorsPerGroup(newVal, oldVal) {
                        let self = this;
                        if (newVal !== oldVal) {
                            self.generateMembers();
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'members_per_group');
                            self.githubUsernameFailed = '';
                            self.organizationPermissionFailed = '';
                        }
                    },
                    selectedToken(newVal, oldVal) {
                        self = this
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'token_id');
                            self.githubUsernameFailed = '';
                            self.organizationPermissionFailed = '';
                        }
                    },
                    organization(newVal, oldVal) {
                        self = this
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'organization');
                            self.githubUsernameFailed = '';
                            self.organizationPermissionFailed = '';
                        }
                    },
                    deadline(newVal, oldVal) {
                        self = this
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'deadline');
                            self.githubUsernameFailed = '';
                            self.organizationPermissionFailed = '';
                        }
                    },
                    members(newVal, oldVal) {
                        self = this
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'members');
                            self.githubUsernameFailed = '';
                            self.organizationPermissionFailed = '';
                        }
                    }
                },
                mounted(){
                    self= this;
                    self.fetchTokens();
                    self.setDateLimits();
                },
            });
        });
    </script>
@endsection
