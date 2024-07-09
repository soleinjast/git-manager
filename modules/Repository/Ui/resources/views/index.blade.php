@extends('layouts.app')

@section('content')
    <style>
        .custom-toast {
            background-color: #d4edda; /* Light green background */
            border-radius: 0.25rem; /* Add some rounding to the corners */
            border-left: 5px solid #28a745; /* Green line on the left */
            color: #155724; /* Dark green text */
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
        .toast-body i {
            color: #155724; /* Dark green icon */
        }
    </style>
    <div id="repository">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="mt-3">
                <!-- Button trigger modal -->
                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    v-on:click="openModal"
                >
                    Add New Repository
                </button>

                <!-- Modal -->
                <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel1">Add New Repository</h5>
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
                                        <select class="form-select" v-model="selectedToken" :style="getValidationStyle('github_token_id')">
                                            <option value="">Select Token</option>
                                            <option v-for="token in tokens" :key="token.id" :value="token.id">@{{ token.login_name}}</option>
                                        </select>
                                        <div v-if="getValidationError('github_token_id')" class="text-danger">@{{ getValidationError('github_token_id') }}</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col mb-3">
                                        <label for="nameBasic" class="form-label">Owner</label>
                                        <input type="text" id="nameBasic" v-model="CreateModalRepoOwner" :style="getValidationStyle('owner')" class="form-control" placeholder="Enter Repository Owner" />
                                        <div v-if="getValidationError('owner')" class="text-danger">@{{ getValidationError('owner') }}</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col mb-3">
                                        <label for="nameBasic" class="form-label">Name</label>
                                        <input type="text" id="nameBasic" v-model="CreateModalRepoName" :style="getValidationStyle('name')" class="form-control" placeholder="Enter Repository Name" />
                                        <div v-if="getValidationError('name')" class="text-danger">@{{ getValidationError('name') }}</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col mb-3">
                                        <label for="deadlineInput" class="form-label">Deadline</label>
                                        <input type="date" id="deadlineInput" v-model="CreateModalRepoDeadline" :min="minDate" :style="getValidationStyle('deadline')" class="form-control" />
                                        <div v-if="getValidationError('deadline')" class="text-danger">@{{ getValidationError('deadline') }}</div>
                                    </div>
                                </div>
                                <div class="divider divider-primary">
                                    <div class="divider-text">Github Link</div>
                                </div>
                                <p style="color: #5f61e6" v-show="!(CreateModalRepoOwner && CreateModalRepoName)">
                                    https://github.com/{owner}/{name}
                                </p>
                                <p style="color: #5f61e6" v-show="CreateModalRepoOwner && CreateModalRepoName">
                                    https://github.com/@{{CreateModalRepoOwner}}/@{{CreateModalRepoName}}
                                </p>
                                <div v-if="repositoryAccessError" class="alert alert-danger" role="alert">@{{ repositoryAccessError }}</div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                    Close
                                </button>
                                <button type="button" class="btn btn-primary" v-on:click="addRepo">Add repository</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <h5 class="card-header">Repositories</h5>
            <div class="row m-3">
                <div class="col-md-4">
                    <label style="color: #5f61e6" class="mb-2">Search by repository name</label>
                    <input type="text" v-model="searchQueryName" class="form-control" placeholder="Search by repository name">
                </div>
                <div class="col-md-4">
                    <label style="color: #5f61e6" class="mb-2">Search by repository owner</label>
                    <input type="text" v-model="searchQueryOwner" class="form-control" placeholder="Search by repository owner">
                </div>
                <div class="col-md-4">
                    <label style="color: #5f61e6" class="mb-2">Filter by deadline</label>
                    <input type="date" v-model="filterDeadline" class="form-control">
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Owner</th>
                        <th>Name</th>
                        <th>Link</th>
                        <th>commits count</th>
                        <th>commit files count</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="repositories.length === 0">
                        <td colspan="8" class="text-center">No records found</td>
                    </tr>
                    <tr v-for="(repo, index) in repositories">
                        <td>
                            <i class="fa-lg me-3"></i>@{{repositories[index].owner}}
                        </td>
                        <td>@{{ repositories[index].name }}</td>
                        <td><a :href="repositories[index].githubUrl" target=”_blank”><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAARNJREFUSEvFlQEOwjAIRdnJ1JOpJ1NPpvmmNL/sl7WLi02WLQv9DyjQxQ5ey8H6NgI4mxmeU3EG308ze5U3vrsrA0DoWsQzDQDuBbay6wFuRXwmg4BgX7MUYI+4i64gCvAu1h66p0pF4zacygunKwLYexkyUfyw8Yv3AQrIdzEAGx4k0BhuHEZMa42CAdFoKwJmRufq3h5gxnsHIXqAsOp+BvjhNgYTdcqAmn4GSA+OAsQCGOHIDPzqkLsFkpUpvB6pJNX5soog6OeAOuYOjlWVDcLGqdjJXs8u6N6pSGLVyHmUDbut9KSpUaOCKyXOFjXvhzo/u3B8tmNSqmgcsOvCidFAJF6NPBa6fTJyJ4802f8AH1WFTxltfZ56AAAAAElFTkSuQmCC" alt="github"/></a></td>
                        <td style="color: green">
                           <span v-if="hasIncreasedCommitCount(repo)" class="up">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(123, 230, 0, 1);transform: ;msFilter:;"><path d="M3 19h18a1.002 1.002 0 0 0 .823-1.569l-9-13c-.373-.539-1.271-.539-1.645 0l-9 13A.999.999 0 0 0 3 19z"></path></svg>
                            </span>
                            @{{ repositories[index].currentCommitsCount }}
                        </td>
                        <td style="color: green">
                           <span v-if="hasIncreasedCommitFilesCount(repo)" >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(123, 230, 0, 1);transform: ;msFilter:;"><path d="M3 19h18a1.002 1.002 0 0 0 .823-1.569l-9-13c-.373-.539-1.271-.539-1.645 0l-9 13A.999.999 0 0 0 3 19z"></path></svg>
                            </span>
                            @{{ repositories[index].currentCommitsFilesCount }}
                        </td>
                        <td><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="green" class="bi bi-check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg></td>
                        <td>@{{repositories[index].deadline}}</td>
                        <td>
                            <button type="button" class="btn btn-danger">Remove</button>
                            {{--                            <button type="button" class="btn btn-info" v-on:click="viewRepoDetails(repo.id)">Info</button>--}}
                        </td>
                    </tr>
                    </tbody>
                    <tfoot class="table-border-bottom-0">
                    <tr>
                        <th>Owner</th>
                        <th>Name</th>
                        <th>Link</th>
                        <th>commits count</th>
                        <th>commit files count</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                    </tfoot>
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
                        <div class="mt-2">Repository added successfully!</div>
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let repository = new Vue({
                el: '#repository',
                data: {
                    CreateModalRepoOwner: null,
                    CreateModalRepoName: null,
                    CreateModalRepoDeadline: null,
                    repositories: [],
                    tokens:[],
                    refreshInterval:null,
                    validationErrors:[],
                    repositoryAccessError: null,
                    previousCommitCounts: {},
                    previousCommitFilesCounts: {},
                    searchQueryName: '',
                    searchQueryOwner: '',
                    filterDeadline: '', // Add filterDeadline to data
                    selectedToken:'',
                    minDate: '',
                },
                methods: {
                    openModal() {
                        self = this
                        self.CreateModalRepoOwner = '';
                        self.CreateModalRepoName = '';
                        self.CreateModalRepoDeadline = '';
                        self.selectedToken = '';
                        self.validationErrors = [];
                        self.showModal();
                        self.setDateLimits();
                    },
                    showModal(){
                        const modalElement = document.getElementById('basicModal');
                        if (modalElement) {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    },
                    setDateLimits() {
                        const today = new Date();
                        const tomorrow = new Date(today);
                        tomorrow.setDate(tomorrow.getDate() + 2);
                        this.minDate = tomorrow.toISOString().split('T')[0];
                        this.CreateModalRepoDeadline = this.minDate;
                    },
                    fetchRepos(){
                        self = this
                        let url = '{{ route('repository.fetch') }}';
                        const params = new URLSearchParams();
                        if (self.searchQueryName) {
                            params.append('search_name', self.searchQueryName);
                        }
                        if (self.searchQueryOwner) {
                            params.append('search_owner', self.searchQueryOwner);
                        }
                        if (self.filterDeadline) {
                            params.append('filter_deadline', self.filterDeadline);
                        }
                        if (params.toString()) {
                            url += `?${params.toString()}`;
                        }
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                data.data.forEach(repo => {
                                    if (!self.previousCommitCounts[repo.id]) {
                                        self.previousCommitCounts[repo.id] = repo.commitsCount;
                                    }
                                    if (!self.previousCommitFilesCounts[repo.id]) {
                                        self.previousCommitFilesCounts[repo.id] = repo.commitsFilesCount;
                                    }

                                    repo.currentCommitsCount = self.previousCommitCounts[repo.id];
                                    repo.currentCommitsFilesCount = self.previousCommitFilesCounts[repo.id];

                                    const commitsIncrement = repo.commitsCount - self.previousCommitCounts[repo.id];
                                    const filesIncrement = repo.commitsFilesCount - self.previousCommitFilesCounts[repo.id];

                                    if (commitsIncrement > 0) {
                                        self.incrementCount(repo, 'commitsCount', commitsIncrement);
                                    }

                                    if (filesIncrement > 0) {
                                        self.incrementCount(repo, 'commitsFilesCount', filesIncrement);
                                    }

                                    self.previousCommitCounts[repo.id] = repo.commitsCount;
                                    self.previousCommitFilesCounts[repo.id] = repo.commitsFilesCount;
                                });

                                self.repositories = data.data;
                            })
                            .catch(error => {
                                console.error('Error fetching repositories:', error);
                            });
                    },
                    incrementCount(repo, countType, increment) {
                        const step = 1; // Define the step increment
                        const interval = 50; // Define the interval in milliseconds
                        let currentCount = repo[countType === 'commitsCount' ? 'currentCommitsCount' : 'currentCommitsFilesCount'];
                        const targetCount = currentCount + increment;

                        const updateCount = () => {
                            if (currentCount < targetCount) {
                                currentCount += step;
                                if (countType === 'commitsCount') {
                                    repo.currentCommitsCount = currentCount;
                                } else {
                                    repo.currentCommitsFilesCount = currentCount;
                                }
                                setTimeout(updateCount, interval);
                            }
                        };

                        updateCount();
                    },
                    fetchTokens(){
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
                    addRepo(){
                        self = this
                        fetch('{{route('repository.create')}}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                owner: self.CreateModalRepoOwner,
                                name: self.CreateModalRepoName,
                                github_token_id: self.selectedToken,
                                deadline: self.CreateModalRepoDeadline
                            })
                        })
                            .then(response =>{
                                if (!response.ok) {
                                    // Check for 422 Bad Request
                                    if (response.status === 422) {
                                        return response.json().then(err => {
                                            self.validationErrors = err.errors;
                                            throw new Error('Validation failed');
                                        });
                                    }
                                    if (response.status === 400) {
                                        return response.json().then(err => {
                                            self.repositoryAccessError = err.message;
                                            throw new Error('Repository access failed');
                                        });
                                    }
                                    throw new Error('Response was not ok!')
                                }
                                return response.json();
                            })
                            .then(data => {
                                $('#basicModal').modal('hide');
                                self.showToast();
                                self.fetchRepos();
                            })
                            .catch(error => {

                            });
                    },
                    {{--viewRepoDetails(repoId){--}}
                        {{--    window.location.href = '{{ route("repository.repository-detail-view", ":repoId") }}'.replace(':repoId', repoId);--}}
                        {{--},--}}
                    deleteRepo(id) {
                        // Delete repository logic
                    },
                    getValidationError(field) {
                        self = this
                        const error = self.validationErrors.find(err => err.field === field);
                        return error ? error.message : null;
                    },
                    getValidationStyle(field) {
                        return this.getValidationError(field) ? 'border-color: red;' : '';
                    },
                    showToast() {
                        const toastElement = document.getElementById('successToast');
                        const toast = new bootstrap.Toast(toastElement);
                        toast.show();
                    }
                },
                mounted(){
                    self = this;
                    self.fetchRepos();
                    self.fetchTokens();
                    self.refreshInterval = setInterval(self.fetchRepos, 3000);
                    self.refreshInterval = setInterval(self.fetchTokens, 3000);
                },
                watch: {
                    CreateModalRepoOwner(newVal, oldVal) {
                        self = this
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'owner');
                            self.repositoryAccessError = null
                        }
                    },
                    CreateModalRepoName(newVal, oldVal) {
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'name');
                            self.repositoryAccessError = null
                        }
                    },
                    selectedToken(newVal, oldVal) {
                        if (newVal !== oldVal) {
                            self.validationErrors = self.validationErrors.filter(err => err.field !== 'github_token_id');
                            self.repositoryAccessError = null
                        }
                    },
                    searchQueryName(newVal, oldVal) {
                        self.fetchRepos();
                    },
                    searchQueryOwner(newVal, oldVal) {
                        self.fetchRepos();
                    },
                    filterDeadline(newVal, oldVal) {
                        self.fetchRepos();
                    }
                },
                beforeDestroy() {
                    self = this;
                    if (self.refreshInterval) {
                        clearInterval(self.refreshInterval);
                    }
                },
                computed: {
                    hasIncreasedCommitCount() {
                        return function(repo) {
                            const previousCount = self.previousCommitCounts[repo.id] || 0;
                            self.previousCommitCounts[repo.id] = repo.commitsCount;
                            return repo.commitsCount > previousCount;
                        }
                    },
                    hasIncreasedCommitFilesCount() {
                        self = this
                        return function(repo) {
                            const previousCount = self.previousCommitFilesCounts[repo.id] || 0;
                            self.previousCommitFilesCounts[repo.id] = repo.commitsFilesCount;
                            return repo.commitsFilesCount > previousCount;
                        }
                    },
                    isLoading() {
                        return function(repo) {
                            return repo.commitsCount === 0 && repo.commitsFilesCount === 0;
                        }
                    }
                }
            });
        });
    </script>

@endsection
