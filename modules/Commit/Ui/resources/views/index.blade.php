@extends('layouts.app')

@section('content')
    <style>
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }

        .btn:hover {
            animation: shake 0.5s;
        }

        .pagination-custom .page-link {
            background-color: white;
            border: 1px solid #ddd;
            color: #333;
        }

        .pagination-custom .page-item.active .page-link {
            background-color: #5e00ff;
            border-color: #4501bb;
            color: white;
        }

        .skeleton-loader {
            background: #e0e0e0;
            background: linear-gradient(90deg, #e0e0e0, #f0f0f0, #e0e0e0);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite linear;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-rect {
            height: 2rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .skeleton-title {
            height: 1.5rem;
            width: 20%;
            margin-bottom: 1rem;
            border-radius: 4px;
            margin-left: 5px;
        }

        .skeleton-button {
            height: 36px;
            width: 120px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .legend-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .legend-item:last-child {
            margin-bottom: 0;
        }

        .color-box {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 8px;
        }

        .color-box.red {
            background-color: #ffcec9;
        }

        .color-box.gray {
            background-color: #e0e0e0;
        }

        .color-box.blue {
            background-color: #cce0ff;
        }

        .icon {
            margin-right: 8px;
        }
    </style>

    <div id="repositoryCommit">
        <div class="card mb-4 shadow-sm" style="background:#303156;">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center">
                    <h6 v-if="loading" class="skeleton-loader skeleton-title"></h6>
                    <h6 v-else class="card-title d-flex align-items-center me-4 mt-2 m-lg-2" style="color: #ffb8d7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                            <path d="M21.993 7.95a.96.96 0 0 0-.029-.214c-.007-.025-.021-.049-.03-.074-.021-.057-.04-.113-.07-.165-.016-.027-.038-.049-.057-.075-.032-.045-.063-.091-.102-.13-.023-.022-.053-.04-.078-.061-.039-.032-.075-.067-.12-.094-.004-.003-.009-.003-.014-.006l-.008-.006-8.979-4.99a1.002 1.002 0 0 0-.97-.001l-9.021 4.99c-.003.003-.006.007-.011.01l-.01.004c-.035.02-.061.049-.094.073-.036.027-.074.051-.106.082-.03.031-.053.067-.079.102-.027.035-.057.066-.079.104-.026.043-.04.092-.059.139-.014.033-.032.064-.041.1a.975.975 0 0 0-.029.21c-.001.017-.007.032-.007.05V16c0 .363.197.698.515.874l8.978 4.987.001.001.002.001.02.011c.043.024.09.037.135.054.032.013.063.03.097.039a1.013 1.013 0 0 0 .506 0c.033-.009.064-.026.097-.039.045-.017.092-.029.135-.054l.02-.011.002-.001.001-.001 8.978-4.987c.316-.176.513-.511.513-.874V7.998c0-.017-.006-.031-.007-.048zm-10.021 3.922L5.058 8.005 7.82 6.477l6.834 3.905-2.682 1.49zm.048-7.719L18.941 8l-2.244 1.247-6.83-3.903 2.153-1.191zM13 19.301l.002-5.679L16 11.944V15l2-1v-3.175l2-1.119v5.705l-7 3.89z"></path>
                        </svg>
                        <span class="ms-2">Repository Name: <span style="color: #ffffff">@{{repositoryInfo.name}}</span></span>
                    </h6>
                    <h6 v-if="loading" class="skeleton-loader skeleton-title"></h6>
                    <h6 v-else class="card-title d-flex align-items-center mt-2 m-lg-2" style="color: #ffb8d7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                            <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3zm9 11v-1a7 7 0 0 0-7-7h-4a7 7 0 0 0-7 7v1h2v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1z"></path>
                        </svg>
                        <span class="ms-2">Repository Owner: <span style="color: #ffffff">@{{repositoryInfo.owner}}</span></span>
                    </h6>
                    <h6 v-if="loading" class="skeleton-loader skeleton-title"></h6>
                    <h6 v-else class="card-title d-flex align-items-center mt-2 m-lg-2" style="color: #ffb8d7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1); vertical-align: middle;">
                            <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path>
                            <path d="M13 7h-2v5.414l3.293 3.293 1.414-1.414L13 11.586z"></path>
                        </svg>
                        <span class="ms-2" style="vertical-align: middle;">Repository Deadline: <span style="color: #ffffff;">@{{repositoryInfo.deadline}}</span></span>
                    </h6>
                    <a type="button" v-if="loading" class="btn btn-primary ms-auto mt-2 mt-lg-0  skeleton-button skeleton-loader"></a>
                    <a v-else target="_blank" :href="repositoryInfo.repositoryUrl" type="button" class="btn btn-primary ms-auto mt-2 mt-lg-0 d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.026 2c-5.509 0-9.974 4.465-9.974 9.974 0 4.406 2.857 8.145 6.821 9.465.499.09.679-.217.679-.481 0-.237-.008-.865-.011-1.696-2.775.602-3.361-1.338-3.361-1.338-.452-1.152-1.107-1.459-1.107-1.459-.905-.619.069-.605.069-.605 1.002.07 1.527 1.028 1.527 1.028.89 1.524 2.336 1.084 2.902.829.091-.645.351-1.085.635-1.334-2.214-.251-4.542-1.107-4.542-4.93 0-1.087.389-1.979 1.024-2.675-.101-.253-.446-1.268.099-2.64 0 0 .837-.269 2.742 1.021a9.582 9.582 0 0 1 2.496-.336 9.554 9.554 0 0 1 2.496.336c1.906-1.291 2.742-1.021 2.742-1.021.545 1.372.203 2.387.099 2.64.64.696 1.024 1.587 1.024 2.675 0 3.833-2.33 4.675-4.552 4.922.355.308.675.916.675 1.846 0 1.334-.012 2.41-.012 2.737 0 .267.178.577.687.479C19.146 20.115 22 16.379 22 11.974 22 6.465 17.535 2 12.026 2z"></path>
                        </svg>
                        <span class="ms-2">Github</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label v-if="!loading" for="author-select" class="form-label">Filter by Author</label>
                <select v-if="!loading" class="form-select" v-model="selectedAuthor" @change="fetchCommits()">
                    <option value="">All Authors</option>
                    <option v-for="collaborator in repositoryInfo.collabs" :key="collaborator.id" :value="collaborator.git_id">@{{ collaborator.login_name }}</option>
                </select>
                <div v-else class="skeleton-loader skeleton-rect"></div>
            </div>
            <div class="col-md-3">
                <label v-if="!loading" for="start-date" class="form-label">Start Date</label>
                <input v-if="!loading" type="date" class="form-control" v-model="startDate" @change="fetchCommits()">
                <div v-else class="skeleton-loader skeleton-rect"></div>
            </div>
            <div class="col-md-3">
                <label v-if="!loading" for="end-date" class="form-label">End Date</label>
                <input v-if="!loading" type="date" class="form-control" v-model="endDate" @change="fetchCommits()">
                <div v-else class="skeleton-loader skeleton-rect"></div>
            </div>
            <div class="col-md-3 d-flex align-items-end ">
                <div v-if="!loading" class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showNonMeaningfulFiles" v-model="showNonMeaningfulFiles">
                    <label class="form-check-label ms-2" for="showNonMeaningfulFiles">Show commits which contain non-meaningful commit files</label>
                </div>
                <div v-else class="skeleton-loader skeleton-rect"></div>
            </div>
        </div>

        <div class="card">
            <h5 v-if="!loading" class="card-header">Commits</h5>
            <div v-if="!loading" class="table-responsive text-nowrap">
                <div class="legend-card">
                    <div class="legend-item">
                        <div class="color-box" style="background-color: #ffcec9;"></div>
                        <span>Commits with non-meaningful files</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background-color: #e0e0e0;"></div>
                        <span>Commits by unknown users</span>
                    </div>
                    <div class="legend-item">
                        <div class="color-box" style="background-color: #cce0ff;"></div>
                        <span>First commits</span>
                    </div>
                </div>
                <table class="table">
                    <thead>
                    <tr>
                        <th>sha</th>
                        <th>author</th>
                        <th>date</th>
                        <th>message</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="commits.length === 0">
                        <td colspan="6" class="text-center">No commits found</td>
                    </tr>
                    <tr v-for="(commit, index) in commits"
                        :style="{
                        'background': (showNonMeaningfulFiles && commit.has_non_meaningFul_files && commit.user.login_name === 'Unknown' && commit.is_first_commit) ?
                            'linear-gradient(135deg, #ffcec9 33%, #e0e0e0 33%, #e0e0e0 66%, #cce0ff 66%)' :
                            (showNonMeaningfulFiles && commit.has_non_meaningFul_files && commit.user.login_name === 'Unknown') ?
                            'linear-gradient(135deg, #ffcec9 50%, #e0e0e0 50%)' :
                            (showNonMeaningfulFiles && commit.has_non_meaningFul_files && commit.is_first_commit) ?
                            'linear-gradient(135deg, #ffcec9 50%, #cce0ff 50%)' :
                            (commit.user.login_name === 'Unknown' && commit.is_first_commit) ?
                            'linear-gradient(135deg, #e0e0e0 50%, #cce0ff 50%)' :
                            (showNonMeaningfulFiles && commit.has_non_meaningFul_files ? '#ffcec9' :
                            (commit.user.login_name === 'Unknown' ? '#e0e0e0' :
                            (commit.is_first_commit ? '#cce0ff' : '')))
                    }"
                        :key="commit.id">
                        <td><a :href="commit.commitDetailDashboardUrl">@{{ commit.sha }}</a>
                            <a target="_blank" :href="commit.githubUrl"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="m21.62 11.108-8.731-8.729a1.292 1.292 0 0 0-1.823 0L9.257 4.19l2.299 2.3a1.532 1.532 0 0 1 1.939 1.95l2.214 2.217a1.53 1.53 0 0 1 1.583 2.531c-.599.6-1.566.6-2.166 0a1.536 1.536 0 0 1-.337-1.662l-2.074-2.063V14.9c.146.071.286.169.407.29a1.537 1.537 0 0 1 0 2.166 1.536 1.536 0 0 1-2.174 0 1.528 1.528 0 0 1 0-2.164c.152-.15.322-.264.504-.339v-5.49a1.529 1.529 0 0 1-.83-2.008l-2.26-2.271-5.987 5.982c-.5.504-.5 1.32 0 1.824l8.731 8.729a1.286 1.286 0 0 0 1.821 0l8.69-8.689a1.284 1.284 0 0 0 .003-1.822"></path></svg></a></td>
                        <td>
                            <div>
                                @{{ commit.author }}
                            </div>
                            <small class="fw-semibold mt-2" style="color: #fd0a9c" v-if="commit.user.university_username !== '' && commit.user.name !== 'Unknown'">university username: @{{commit.user.university_username}}</small>
                        </td>
                        <td>@{{ commit.date }}</td>
                        <td>@{{ commit.message }}</td>
                    </tr>
                    </tbody>
                    <tfoot class="table-border-bottom-0">
                    <tr>
                        <th>sha</th>
                        <th>author</th>
                        <th>date</th>
                        <th>message</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div v-else>
                <div v-for="n in 10" :key="n" class="skeleton-loader skeleton-rect"></div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4" v-if="pagination.last_page > 1">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-custom">
                    <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                        <a class="page-link" href="javascript:void(0);" @click="fetchCommits(pagination.first_page_url)">
                            <i class="tf-icon bx bx-chevrons-left"></i>
                        </a>
                    </li>
                    <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                        <a class="page-link" href="javascript:void(0);" @click="fetchCommits(pagination.prev_page_url)">
                            <i class="tf-icon bx bx-chevron-left"></i>
                        </a>
                    </li>
                    <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page === pagination.current_page }">
                        <a class="page-link" href="javascript:void(0);" @click="fetchCommits(getPageUrl(page))">@{{ page }}</a>
                    </li>
                    <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                        <a class="page-link" href="javascript:void(0);" @click="fetchCommits(pagination.next_page_url)">
                            <i class="tf-icon bx bx-chevron-right"></i>
                        </a>
                    </li>
                    <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                        <a class="page-link" href="javascript:void(0);" @click="fetchCommits(pagination.last_page_url)">
                            <i class="tf-icon bx bx-chevrons-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Vue({
                el: '#repositoryCommit',
                data: {
                    loading: true,
                    showNonMeaningfulFiles: false,
                    commits: [],
                    selectedAuthor: '',
                    startDate: '',
                    endDate: '',
                    repositoryInfo: null,
                    pagination: {
                        current_page: null,
                        last_page: null,
                        next_page_url: null,
                        prev_page_url: null,
                        first_page_url: null,
                        last_page_url: null,
                    },
                    fetchUrl: '{{ route('commit.fetch', ['repoId' => ':repoId']) }}',
                },
                computed: {
                    pages() {
                        self = this;
                        let pages = [];
                        for (let i = 1; i <= self.pagination.last_page; i++) {
                            pages.push(i);
                        }
                        return pages;
                    }
                },
                methods: {
                    fetchCommits(url) {
                        self = this;
                        self.loading = true;
                        if (!url) {
                            const repoId = window.location.pathname.split('/').slice(-2, -1)[0];
                            url = self.fetchUrl.replace(':repoId', repoId);
                        }
                        if (!url.includes('author')) {
                            url += `?author=${self.selectedAuthor}`;
                        }
                        if (!url.includes('start_date')) {
                            url += `&start_date=${self.startDate}`;
                        }
                        if (!url.includes('end_date')) {
                            url += `&end_date=${self.endDate}`;
                        }
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                self.commits = data.data.commits;
                                self.repositoryInfo = data.data.repository;
                                self.pagination.current_page = data.data.current_page;
                                self.pagination.last_page = data.data.last_page;
                                self.pagination.next_page_url = self.updateUrl(data.data.next_page_url);
                                self.pagination.prev_page_url = self.updateUrl(data.data.prev_page_url);
                                self.pagination.first_page_url = self.updateUrl(self.getPageUrl(1));
                                self.pagination.last_page_url = self.updateUrl(self.getPageUrl(self.pagination.last_page));
                                self.loading = false;
                            })
                            .catch(error => {
                                console.error('Error fetching commits:', error);
                                self.loading = false;
                            });
                    },
                    updateUrl(url) {
                        console.log(url)
                        self = this
                        if (!url) return null;
                        const urlObj = new URL(url);
                        urlObj.searchParams.set('author', self.selectedAuthor);
                        urlObj.searchParams.set('start_date', self.startDate);
                        urlObj.searchParams.set('end_date', self.endDate);
                        return urlObj.toString();
                    },
                    getPageUrl(page) {
                        self = this
                        const repoId = window.location.pathname.split('/').slice(-2, -1)[0];
                        return `${self.fetchUrl.replace(':repoId', repoId)}?page=${page}&author=${self.selectedAuthor}&start_date=${self.startDate}&end_date=${self.endDate}`;
                    },
                },
                mounted() {
                    self = this;
                    self.fetchCommits();
                }
            });
        });
    </script>
@endsection
