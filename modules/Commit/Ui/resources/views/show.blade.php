@extends('layouts.app')

@section('content')
    <div id="commitDetailApp" class="container mt-4">
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
        <div v-if="loading" class="text-center">
            <p>Loading...</p>
        </div>
        <div v-else>
            <div class="card mb-4 border-info">
                <div class="card-header d-flex justify-content-center mb-2" style="background-color: #28154b; color: white;">
                    <h5 class="mb-0" style="color: #FFFFFF">Commit Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong><i class="bi bi-shield-lock-fill"></i> SHA:</strong></td>
                            <td>@{{ commit.sha }}</td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-person-fill"></i> Author:</strong></td>
                            <td>@{{ commit.author }}</td>
                        </tr>
                        <tr v-if="commit.user.login_name !== 'Unknown' && commit.user.university_username !== ''">
                            <td><strong><i class="bi bi-person-fill"></i> University Username:</strong></td>
                            <td>@{{ commit.user.university_username }}</td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-calendar-event-fill"></i> Date:</strong></td>
                            <td>@{{ commit.date }}</td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-chat-left-text-fill"></i> Message:</strong></td>
                            <td>@{{ commit.message }}</td>
                        </tr>
                        <tr>
                            <td><strong><i class="bi bi-link-45deg"></i> GitHub URL:</strong></td>
                            <td><a :href="commit.githubUrl" target="_blank">View on GitHub</a></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <h5 class="card-header">Commit Files</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Changes</th>
                            <th>Meaningful</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-if="commitFiles.length === 0">
                            <td colspan="6" class="text-center">No commit files found</td>
                        </tr>
                        <tr v-for="file in commitFiles" :key="file.id" :class="{ 'table-danger': !file.meaningful }">
                            <td>@{{ file.filename }}</td>
                            <td>@{{ file.status }}</td>
                            <td>
                                <button class="btn btn-primary btn-sm" @click="viewChanges(file.changes)">View Changes</button>
                            </td>
                            <td>@{{ file.meaningful ? 'Yes' : 'No' }}
                                <span v-if="file.meaningful" class="badge bg-success">Meaningful</span>
                                <span v-else class="badge bg-danger">Non Meaningful</span>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot class="table-border-bottom-0">
                        <tr>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Changes</th>
                            <th>Meaningful</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal for viewing changes -->
        <div class="modal fade" id="changesModal" tabindex="-1" role="dialog" aria-labelledby="changesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changesModalLabel">Changes</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <pre class="notebook-style" v-html="formattedChanges"></pre>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .notebook-style {
            background: #f7f7f9;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e1e1e8;
            white-space: pre-wrap; /* Ensure the content wraps correctly */
            word-wrap: break-word; /* Break long words to fit within the container */
        }
        .notebook-style .added {
            display: block;
            background-color: #eaffea;
            border-left: 4px solid #32cd32;
            padding-left: 8px;
        }
        .notebook-style .removed {
            display: block;
            background-color: #ffecec;
            border-left: 4px solid #ff6347;
            padding-left: 8px;
        }
        .notebook-style .hunk-header {
            display: block;
            background-color: #e0e0e0;
            border-left: 4px solid #666;
            padding-left: 8px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Vue({
                el: '#commitDetailApp',
                data: {
                    repositoryInfo: {},
                    commit: {},
                    commitFiles: [],
                    loading: true,
                    repoId: null,
                    commitSha: null,
                    formattedChanges: ''
                },
                mounted() {
                    self = this;
                    self.extractUrlParams();
                    self.fetchCommitDetails();
                },
                methods: {
                    extractUrlParams() {
                        const pathArray = window.location.pathname.split('/');
                        self.repoId = pathArray[pathArray.indexOf('repository') + 1];
                        self.commitSha = pathArray[pathArray.indexOf('commits') + 1];
                    },
                    fetchCommitDetails() {
                        const url = `{{ route('commit.fetch-commit-detail', ['repoId' => ':repoId', 'sha' => ':sha']) }}`
                            .replace(':repoId', self.repoId)
                            .replace(':sha', self.commitSha);
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                self.commit = data.data.commit;
                                self.repositoryInfo = data.data.repository;
                                self.commitFiles = data.data.commitFiles;
                                self.loading = false;
                            })
                            .catch(error => {
                                console.error('Error fetching commit details:', error);
                                self.loading = false;
                            });
                    },
                    viewChanges(changes) {
                        self = this;
                        self.formattedChanges = self.formatChanges(changes);
                        new bootstrap.Modal(document.getElementById('changesModal')).show();
                    },
                    formatChanges(changes) {
                        const escapeHtml = (unsafe) => {
                            return unsafe
                                .replace(/&/g, "&amp;")
                                .replace(/</g, "&lt;")
                                .replace(/>/g, "&gt;")
                                .replace(/"/g, "&quot;")
                                .replace(/'/g, "&#039;");
                        };

                        return changes
                            .split('\n')
                            .map(line => {
                                if (line.startsWith('+')) {
                                    return `<span class="added">${escapeHtml(line)}</span>`;
                                } else if (line.startsWith('-')) {
                                    return `<span class="removed">${escapeHtml(line)}</span>`;
                                } else if (line.startsWith('@@')) {
                                    return `<span class="hunk-header">${escapeHtml(line)}</span>`;
                                } else {
                                    return `<span>${escapeHtml(line)}</span>`;
                                }
                            })
                            .join('\n');
                    }
                }
            });
        });
    </script>
@endsection
