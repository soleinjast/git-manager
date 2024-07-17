@extends('layouts.app')

@section('content')
    <style>
        .custom-toast {
            background-color: #d4edda; /* Light green background */
            border-radius: 0.25rem; /* Add some rounding to the corners */
            border-left: 5px solid #28a745; /* Green line on the left */
            color: #155724; /* Dark green text */
        }
        .error-toast {
            background-color: #f8d7da; /* Light red background */
            border-radius: 0.25rem; /* Add some rounding to the corners */
            border-left: 5px solid #dc3545; /* Red line on the left */
            color: #721c24; /* Dark red text */
        }
        .toast-container {
            z-index: 1060; /* Ensure it appears above other elements */
        }
        .toast {
            opacity: 1; /* Full opacity */
            padding: 0.5rem; /* Reduce padding */
        }
        .toast .btn-close {
            color: #155724; /* Dark green close button */
        }
        .error-toast .btn-close {
            color: #721c24; /* Dark red close button */
        }
        .toast-body i {
            color: #155724; /* Dark green icon */
        }
        .error-toast .toast-body i {
            color: #721c24; /* Dark red icon */
        }
    </style>
    <div id="token">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="mt-3">
                <!-- Button trigger modal -->
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    @click="openModal"
                >
                    Add New Token
                </button>
            </div>
        </div>

        <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Add New Token</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label for="nameBasic" class="form-label">Token</label>
                                <input :style="getValidationStyle('token')" type="text" id="nameBasic" v-model="tokenString" class="form-control" placeholder="Enter User Token" />
                                <div v-if="getValidationError('token')" class="text-danger mt-1">@{{ getValidationError('token') }}</div>
                            </div>
                        </div>
                        <div v-if="tokenAccessError" class="alert alert-danger" role="alert">@{{ tokenAccessError }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="button" class="btn btn-primary" @click="addToken">Add Token</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this token?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" @click="deleteToken">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h5 class="card-header">Tokens</h5>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover table-striped">
                    <thead>
                    <tr>
                        <th class="text-center">Token</th>
                        <th class="text-center">GitHub Username</th>
                        <th class="text-center">GitHub ID</th>
                        <th class="text-center">Created At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    <tr v-if="tokens.length === 0">
                        <td colspan="5" class="text-center">No records found</td>
                    </tr>
                    <tr v-for="(token, index) in tokens" :key="token.id">
                        <td class="text-center"><i class="fab fa-angular fa-lg text-danger me-3"></i> <strong>@{{token.token}}</strong></td>
                        <td class="text-center">@{{token.login_name}}</td>
                        <td class="text-center">
                            <span :class="getBadgeClass(token.githubId)" class="badge me-1">@{{token.githubId}}</span>
                        </td>
                        <td class="text-center">
                            @{{token.created_at}}
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger" @click="confirmDelete(token.id)">Delete</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast custom-toast align-items-center" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex align-items-center">
                    <div class="toast-body">
                        <i class="bi bi-check-circle-fill me-2" style="font-size: 1.5rem; vertical-align: middle;"></i>
                        <div>
                            <strong style="vertical-align: middle;">Success</strong>
                        </div>
                        <div class="mt-2" id="successToastMessage"></div>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
            <div class="toast error-toast align-items-center" id="errorToast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex align-items-center">
                    <div class="toast-body">
                        <i class="bi bi-exclamation-circle-fill me-2" style="font-size: 1.5rem; vertical-align: middle;"></i>
                        <div>
                            <strong style="vertical-align: middle;">Error</strong>
                        </div>
                        <div class="mt-2" id="errorToastMessage">An error occurred.</div>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let token = new Vue({
                el: '#token',
                data() {
                    return {
                        tokenString: null,
                        tokenAccessError: null,
                        validationErrors: [],
                        tokens: [],
                        tokenIdToDelete: null
                    }
                },
                watch: {
                    tokenString(newVal, oldVal) {
                        let self = this
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'token');
                            self.tokenAccessError = null
                        }
                    }
                },
                mounted() {
                    let self = this;
                    self.fetchTokens();
                },
                methods: {
                    fetchTokens() {
                        let self = this;
                        let url = '{{ route('token.fetch') }}';
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                self.tokens = data.data;
                            })
                            .catch(error => {
                                console.error('Error fetching tokens:', error);
                            });
                    },
                    openModal() {
                        let self = this
                        self.tokenString = null;
                        self.validationErrors = [];
                        self.showModal()
                    },
                    showModal() {
                        const modalElement = document.getElementById('basicModal');
                        if (modalElement) {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    },
                    addToken() {
                        let self = this
                        fetch('{{route('token.create')}}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                token: self.tokenString,
                            })
                        })
                            .then(response => {
                                if (!response.ok) {
                                    if (response.status === 422) {
                                        return response.json().then(err => {
                                            self.validationErrors = err.errors;
                                            throw new Error('Validation failed');
                                        });
                                    }
                                    if (response.status === 401) {
                                        return response.json().then(err => {
                                            self.tokenAccessError = err.message;
                                            throw new Error('Token access failed');
                                        });
                                    }
                                    throw new Error('Response was not ok!')
                                }
                                return response.json();
                            })
                            .then(data => {
                                $('#basicModal').modal('hide');
                                self.fetchTokens();
                                self.showToast('Token added successfully!', 'success');
                            })
                            .catch(error => {
                                console.error('Error adding token:', error);
                            });
                    },
                    confirmDelete(tokenId) {
                        this.tokenIdToDelete = tokenId;
                        const modalElement = document.getElementById('deleteModal');
                        if (modalElement) {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    },
                    deleteToken() {
                        let self = this;
                        const tokenId = self.tokenIdToDelete;
                        fetch('{{ route('token.delete', ['tokenId' => ':tokenId']) }}'.replace(':tokenId', tokenId), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(err => {
                                        throw new Error(err.message);
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                self.fetchTokens();
                                self.tokenIdToDelete = null;
                                $('#deleteModal').modal('hide');
                                self.showToast('Token deleted successfully!', 'success');
                            })
                            .catch(error => {
                                self.showToast(error.message, 'error');
                                self.tokenIdToDelete = null;
                                $('#deleteModal').modal('hide');
                                console.error('Error deleting token:', error);
                            });
                    },
                    getValidationError(field) {
                        let self = this
                        const error = self.validationErrors.find(err => err.field === field);
                        return error ? error.message : null;
                    },
                    getValidationStyle(field) {
                        let self = this
                        return this.getValidationError(field) ? 'border-color: red;' : '';
                    },
                    getBadgeClass(githubId) {
                        if (githubId % 2 === 0) {
                            return 'bg-primary';
                        } else if (githubId % 3 === 0) {
                            return 'bg-success';
                        } else {
                            return 'bg-info';
                        }
                    },
                    showToast(message, type) {
                        const toastElement = type === 'success' ? document.getElementById('successToast') : document.getElementById('errorToast');
                        if (type === 'success') {
                            document.getElementById('successToastMessage').textContent = message;
                        } else if (type === 'error') {
                            document.getElementById('errorToastMessage').textContent = message;
                        }
                        const toast = new bootstrap.Toast(toastElement);
                        toast.show();
                    }
                }
            });
        });
    </script>
@endsection
