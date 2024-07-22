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
        @keyframes blinker {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }

        .blinker {
            animation: blinker 1.5s linear infinite;
            font-size: 1.5rem;
            color: #fff;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 2px solid #ff6347;
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

        .skeleton-text {
            height: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .skeleton-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .skeleton-rect {
            height: 2rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .skeleton-title {
            height: 1.5rem;
            width: 50%;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .user-picture {
            border: 3px solid #5f61e6;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .user-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .invitation-status {
            color: #e63946;
        }

        .icon-text {
            display: inline-flex;
            align-items: center;
        }

        .icon-text svg {
            margin-right: 8px;
        }
    </style>
    <div id="repositoryInfo">
        <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel1">Update Collaborator</h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-header d-flex flex-column align-items-center">
                            <img :src="UpdateCollaborator.avatarUrl" alt="User Picture" class="rounded-circle mb-3 user-picture" style="width: 100px; height: 100px;">

                            <hr class="w-100 mt-0 mb-3">
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label for="nameBasic" class="form-label">Github Id</label>
                                <input type="text" id="nameBasic" class="form-control" v-model="UpdateCollaborator.githubId" disabled/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label for="nameBasic" class="form-label">Github Username</label>
                                <input type="text" id="nameBasic" class="form-control" v-model="UpdateCollaborator.githubUsername" disabled/>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label for="nameBasic" class="form-label">Student Username</label>
                                <input type="text" id="nameBasic" class="form-control" v-model="UpdateCollaborator.university_username"/>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="button" class="btn btn-primary" v-on:click="updateCollaboratorInfo" >Update</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="loading">
                <!-- Skeleton loader for the card -->
                <div class="card mb-4 shadow-sm skeleton-loader" style="height: 150px;"></div>
                <!-- Skeleton loaders for other components -->
                <div class="row">
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 skeleton-loader">
                            <div class="card-header d-flex align-items-center justify-content-between pb-0">
                                <div class="card-title mb-0 skeleton-title"></div>
                            </div>
                            <div class="card-body">
                                <div class="skeleton-text"></div>
                                <div class="skeleton-text"></div>
                                <div class="skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card skeleton-loader">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0 skeleton-avatar"></div>
                                </div>
                                <div class="skeleton-text"></div>
                                <div class="skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card skeleton-loader">
                            <div class="card-body">
                                <div class="card-title d-flex align-items-start justify-content-between">
                                    <div class="avatar flex-shrink-0 skeleton-avatar"></div>
                                </div>
                                <div class="skeleton-text"></div>
                                <div class="skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <div v-else>
            <div  v-if="repositoryInfo.isCloseToDeadline" class="blinker blinking">⚠️ The repository is close to the deadline! ⚠️</div>
            <div class="card mb-4 shadow-sm" style="background:#303156;">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center">
                        <h6 class="card-title d-flex align-items-center me-4 mt-2 m-lg-2" style="color: #ffb8d7;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                                <path d="M21.993 7.95a.96.96 0 0 0-.029-.214c-.007-.025-.021-.049-.03-.074-.021-.057-.04-.113-.07-.165-.016-.027-.038-.049-.057-.075-.032-.045-.063-.091-.102-.13-.023-.022-.053-.04-.078-.061-.039-.032-.075-.067-.12-.094-.004-.003-.009-.003-.014-.006l-.008-.006-8.979-4.99a1.002 1.002 0 0 0-.97-.001l-9.021 4.99c-.003.003-.006.007-.011.01l-.01.004c-.035.02-.061.049-.094.073-.036.027-.074.051-.106.082-.03.031-.053.067-.079.102-.027.035-.057.066-.079.104-.026.043-.04.092-.059.139-.014.033-.032.064-.041.1a.975.975 0 0 0-.029.21c-.001.017-.007.032-.007.05V16c0 .363.197.698.515.874l8.978 4.987.001.001.002.001.02.011c.043.024.09.037.135.054.032.013.063.03.097.039a1.013 1.013 0 0 0 .506 0c.033-.009.064-.026.097-.039.045-.017.092-.029.135-.054l.02-.011.002-.001.001-.001 8.978-4.987c.316-.176.513-.511.513-.874V7.998c0-.017-.006-.031-.007-.048zm-10.021 3.922L5.058 8.005 7.82 6.477l6.834 3.905-2.682 1.49zm.048-7.719L18.941 8l-2.244 1.247-6.83-3.903 2.153-1.191zM13 19.301l.002-5.679L16 11.944V15l2-1v-3.175l2-1.119v5.705l-7 3.89z"></path>
                            </svg>
                            <span class="ms-2">Repository Name: <span style="color: #ffffff">@{{repositoryInfo.repositoryName}}</span></span>
                        </h6>
                        <h6 class="card-title d-flex align-items-center mt-2 m-lg-2" style="color: #ffb8d7;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                                <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3zm9 11v-1a7 7 0 0 0-7-7h-4a7 7 0 0 0-7 7v1h2v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1z"></path>
                            </svg>
                            <span class="ms-2">Repository Owner: <span style="color: #ffffff">@{{repositoryInfo.repositoryOwner}}</span></span>
                        </h6>
                        <h6 class="card-title d-flex align-items-center mt-2 m-lg-2" style="color: #ffb8d7;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1); vertical-align: middle;">
                                <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path>
                                <path d="M13 7h-2v5.414l3.293 3.293 1.414-1.414L13 11.586z"></path>
                            </svg>
                            <span class="ms-2" style="vertical-align: middle;">Repository Deadline: <span style="color: #ffffff;">@{{repositoryInfo.deadline}}</span></span>
                        </h6>

                        <a target="_blank" :href="repositoryInfo.repositoryUrl" type="button" class="btn btn-primary ms-auto mt-2 mt-lg-0 d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.026 2c-5.509 0-9.974 4.465-9.974 9.974 0 4.406 2.857 8.145 6.821 9.465.499.09.679-.217.679-.481 0-.237-.008-.865-.011-1.696-2.775.602-3.361-1.338-3.361-1.338-.452-1.152-1.107-1.459-1.107-1.459-.905-.619.069-.605.069-.605 1.002.07 1.527 1.028 1.527 1.028.89 1.524 2.336 1.084 2.902.829.091-.645.351-1.085.635-1.334-2.214-.251-4.542-1.107-4.542-4.93 0-1.087.389-1.979 1.024-2.675-.101-.253-.446-1.268.099-2.64 0 0 .837-.269 2.742 1.021a9.582 9.582 0 0 1 2.496-.336 9.554 9.554 0 0 1 2.496.336c1.906-1.291 2.742-1.021 2.742-1.021.545 1.372.203 2.387.099 2.64.64.696 1.024 1.587 1.024 2.675 0 3.833-2.33 4.675-4.552 4.922.355.308.675.916.675 1.846 0 1.334-.012 2.41-.012 2.737 0 .267.178.577.687.479C19.146 20.115 22 16.379 22 11.974 22 6.465 17.535 2 12.026 2z"></path>
                            </svg>
                            <span class="ms-2">Github</span>
                        </a>

                    </div>
                </div>
            </div>
            <div class="row" v-if="!loading">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between pb-0">
                            <div class="card-title mb-0">
                                <h5 class="m-0 me-2">Repository Collaborators</h5>
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="orderStatistics" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <h2 class="mb-2" v-html="collabs.length"></h2>
                                    <span>Info</span>
                                </div>
                            </div>
                            <ul class="p-0 m-0">
                                <li class="d-flex mb-4 pb-1" v-for="collaborator in collabs" :key="collaborator.id">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <div class="avatar avatar-online">
                                            <img :src="collaborator.avatar_url" alt class="w-px-40 h-auto rounded-circle" />
                                        </div>
                                    </div>
                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex flex-column me-2">
                                            <a :href="collaborator.github_url" target="_blank" v-if="collaborator.name !== ''">
                                                <h6 class="mb-0">@{{ collaborator.name }}</h6>
                                            </a>
                                            <a :href="collaborator.github_url" target="_blank" v-else-if="collaborator.login_name !== ''">
                                                <h6 class="mb-0">@{{ collaborator.login_name }}</h6>
                                            </a>
                                            <small class="fw-semibold mt-2" style="color: #fd0a9c" v-if="collaborator.university_username !== ''">
                                                university username: @{{ collaborator.university_username }}
                                            </small>
                                            <small class="fw-semibold mt-2" style="color: #5f61e6">
                                                @{{ collaborator.commit_count }} commits
                                            </small>
                                            <small class="fw-semibold mt-2 invitation-status" v-if="collaborator.status === 'pending'">
                                    <span class="icon-text">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 0, 0, 1)"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
                                        <span>Invitation sent, awaiting acceptance.</span>
                                    </span>
                                            </small>
                                        </div>
                                        <div class="user-progress" v-if="collaborator.status !== 'pending'">
                                            <button class="btn btn-primary btn-sm" v-on:click="showModal(collaborator)">Update</button>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAAAaJJREFUaEPtmOFOAyEQhOmTWZ9MfbLqk2nHgKFk4diZNXrJ3p/a9ID5Zpblzks5+XU5uf6SAH+dYCaQCYgOZAmJBsrDMwHZQnGCTGAw8Fq/4/OplNK+v5dSPupvr6LpD8MjE4Cwl01xb/f7QkAiAOAyhDe3Nxm+b5NBVACP6zOw5zs8Soy6FAA4fjNWbfXeROGzlcusxGgIBeDTEL9TElZqgASE+2IBLBE74pvAMAgWYHTfI34F4dbjHlDreaxlZh6AYA/13cu9F5iFx/gZ92cpuPeCBWBtztXmUgBmnWy13oPmCAAmxSbwXwC463aw15t4eAIKAHOSHwIcHSZj51D2gNwQmPod69bdOTqH5POEAbD6N5OCVT5uPe4B1b2xjLyPxuqjyE+ILICVwi7EbONSWqhBFX/Vw1FSuCB29prZNwu6kykAEMAcRGOXY/ZPSAmtniqPWjF+R/eCePptDJOoCfRCPYeS5Hq/aCRAnwj+tv6t0r9m7qR0eM9vABwuGnlDAkS6ycyVCTCuRY7JBCLdZObKBBjXIsdkApFuMnOdPoEvx+ZNMQfU5y8AAAAASUVORK5CYII=" alt="git"/>
                                </div>
                            </div>
                            <span class="d-block mb-1">Total Commits <a :href="repositoryInfo.commitDashboardUrl" class="align-content-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(10, 65, 255, 1)"><path d="M8.465 11.293c1.133-1.133 3.109-1.133 4.242 0l.707.707 1.414-1.414-.707-.707c-.943-.944-2.199-1.465-3.535-1.465s-2.592.521-3.535 1.465L4.929 12a5.008 5.008 0 0 0 0 7.071 4.983 4.983 0 0 0 3.535 1.462A4.982 4.982 0 0 0 12 19.071l.707-.707-1.414-1.414-.707.707a3.007 3.007 0 0 1-4.243 0 3.005 3.005 0 0 1 0-4.243l2.122-2.121z"></path><path d="m12 4.929-.707.707 1.414 1.414.707-.707a3.007 3.007 0 0 1 4.243 0 3.005 3.005 0 0 1 0 4.243l-2.122 2.121c-1.133 1.133-3.109 1.133-4.242 0L10.586 12l-1.414 1.414.707.707c.943.944 2.199 1.465 3.535 1.465s2.592-.521 3.535-1.465L19.071 12a5.008 5.008 0 0 0 0-7.071 5.006 5.006 0 0 0-7.071 0z"></path></svg></a></span>
                            <h3 class="text-primary card-title text-nowrap mb-2">@{{repositoryInfo.commitsCount}}</h3>
                            <span class="d-block mb-1">First Commit</span>
                            <span class="text-primary card-title text-nowrap mb-2" v-if="repositoryInfo.firstCommit !== ''">@{{repositoryInfo.firstCommit}}</span>
                            <span class="text-primary card-title text-nowrap mb-2" v-if="repositoryInfo.firstCommit === ''">No Date Specified</span>
                            <span class="d-block mb-1">Last Commit</span>
                            <span class="text-primary card-title text-nowrap mb-2" v-if="repositoryInfo.lastCommit !== ''">@{{repositoryInfo.lastCommit}}</span>
                            <span class="text-primary card-title text-nowrap mb-2" v-if="repositoryInfo.lastCommit === ''">No Date Specified</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAAAaJJREFUaEPtmOFOAyEQhOmTWZ9MfbLqk2nHgKFk4diZNXrJ3p/a9ID5Zpblzks5+XU5uf6SAH+dYCaQCYgOZAmJBsrDMwHZQnGCTGAw8Fq/4/OplNK+v5dSPupvr6LpD8MjE4Cwl01xb/f7QkAiAOAyhDe3Nxm+b5NBVACP6zOw5zs8Soy6FAA4fjNWbfXeROGzlcusxGgIBeDTEL9TElZqgASE+2IBLBE74pvAMAgWYHTfI34F4dbjHlDreaxlZh6AYA/13cu9F5iFx/gZ92cpuPeCBWBtztXmUgBmnWy13oPmCAAmxSbwXwC463aw15t4eAIKAHOSHwIcHSZj51D2gNwQmPod69bdOTqH5POEAbD6N5OCVT5uPe4B1b2xjLyPxuqjyE+ILICVwi7EbONSWqhBFX/Vw1FSuCB29prZNwu6kykAEMAcRGOXY/ZPSAmtniqPWjF+R/eCePptDJOoCfRCPYeS5Hq/aCRAnwj+tv6t0r9m7qR0eM9vABwuGnlDAkS6ycyVCTCuRY7JBCLdZObKBBjXIsdkApFuMnOdPoEvx+ZNMQfU5y8AAAAASUVORK5CYII=" alt="git"/>
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                                </div>
                            </div>
                            <span class="d-block mb-1">Total Commit Files</span>
                            <h3 class="text-primary card-title text-nowrap mb-2">@{{repositoryInfo.commitsFilesCount}}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card" style="height: 100%">
                        <div class="card-body">
                            <h5 class="card-title">Commit Files Distribution</h5>
                            <canvas id="commitFilesChart" width="100" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Repository Daily Commits Trend</h5>
                            <div class="chart-container">
                                <canvas id="commitFilesBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Commit Files Distribution by User</h5>
                            <div class="chart-container">
                                <canvas id="commitFlowChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let repositoryInfo = new Vue({
                el: '#repositoryInfo',
                data: {
                    loading: true,
                    collabs: [],
                    repositoryInfo: {
                        repositoryName: '',
                        repositoryOwner: '',
                        commitsCount: 0,
                        commitsFilesCount: 0,
                        repositoryUrl: '',
                        commitDashboardUrl: '',
                        repositoryMeaningFullCommitCount: 0,
                        repositoryNotMeaningFullCommitCount: 0,
                        lastCommit: '',
                        firstCommit: '',
                        isCloseToDeadline: false,
                        deadline: ''
                    },
                    UpdateCollaborator: {
                        githubUsername: '',
                        githubId: '',
                        avatarUrl: '',
                        name: '',
                        university_username: '',
                        status: ''
                    },
                    fetchRepoInfoUrl: '{{ route("repository.info", ["repoId" => ":repoId"]) }}'
                },
                methods: {
                    fetchRepoInfo() {
                        self = this;
                        const repoId = window.location.pathname.split('/').pop();
                        const url = self.fetchRepoInfoUrl.replace(':repoId', repoId);
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                self.collabs = data.data.collabs;
                                self.repositoryInfo.commitsCount = data.data.commitsCount;
                                self.repositoryInfo.commitsFilesCount = data.data.commitsFilesCount;
                                self.repositoryInfo.repositoryName = data.data.name;
                                self.repositoryInfo.repositoryOwner = data.data.owner;
                                self.repositoryInfo.repositoryUrl = data.data.repositoryUrl;
                                self.repositoryInfo.repositoryMeaningFullCommitCount = data.data.meaningfulCommitFilesCount;
                                self.repositoryInfo.repositoryNotMeaningFullCommitCount = data.data.NotMeaningfulCommitFilesCount;
                                self.repositoryInfo.lastCommit = data.data.firstCommit;
                                self.repositoryInfo.firstCommit = data.data.lastCommit;
                                self.repositoryInfo.commitDashboardUrl = data.data.commitDashboardUrl;
                                self.repositoryInfo.isCloseToDeadline = data.data.isCloseToDeadline;
                                self.repositoryInfo.deadline = data.data.deadline;
                                self.loading = false;
                                self.$nextTick(() => {
                                    self.renderChart();
                                    self.fetchCommitFlowData(repoId);
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching repository info:', error);
                                self.loading = false;
                            });
                    },
                    fetchCommitFlowData(repoId) {
                        const url = `${repoId}/commit-flow`;
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                const labels = Object.keys(data.data);
                                const values = Object.values(data.data);

                                const ctx = document.getElementById('commitFlowChart').getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Commits',
                                            data: values,
                                            backgroundColor: 'rgb(94,0,255)',
                                            borderColor: 'rgb(94,0,255)',
                                            borderWidth: 1
                                        }]
                                    },
                                    options: {
                                        scales: {
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching commit flow data:', error);
                            });
                    },
                    updateCollaboratorInfo() {
                        const repoId = window.location.pathname.split('/').pop();
                        const self = this;
                        const url = '{{ route("user.update") }}';
                        const data = {
                            login_name: self.UpdateCollaborator.githubUsername,
                            git_id: self.UpdateCollaborator.githubId,
                            university_username: self.UpdateCollaborator.university_username,
                            status: self.UpdateCollaborator.status,
                            repository_id: repoId,
                            name: self.UpdateCollaborator.name,
                            avatar_url: self.UpdateCollaborator.avatarUrl,
                            _token: '{{ csrf_token() }}'
                        };
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': data._token
                            },
                            body: JSON.stringify(data)
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    // Update the collaborator information in the UI
                                    const collaboratorIndex = self.collabs.findIndex(collab => collab.git_id === self.UpdateCollaborator.githubId);
                                    if (collaboratorIndex !== -1) {
                                        self.collabs[collaboratorIndex].university_username = self.UpdateCollaborator.university_username;
                                    }
                                    // Close the modal
                                    const modalElement = document.getElementById('basicModal');
                                    if (modalElement) {
                                        const modal = bootstrap.Modal.getInstance(modalElement);
                                        modal.hide();
                                    }
                                } else {
                                    alert('Failed to update collaborator.');
                                }
                            })
                            .catch(error => {
                                console.error('Error updating collaborator:', error);
                                alert('An error occurred while updating the collaborator.');
                            });
                    },
                    renderChart() {
                        self = this;
                        // Destroy existing charts if they exist
                        if (self.pieChart) {
                            self.pieChart.destroy();
                        }
                        if (self.barChart) {
                            self.barChart.destroy();
                        }
                        const ctx = document.getElementById('commitFilesChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: ['Meaningful', 'Not Meaningful'],
                                datasets: [{
                                    data: [self.repositoryInfo.repositoryMeaningFullCommitCount, self.repositoryInfo.repositoryNotMeaningFullCommitCount],
                                    backgroundColor: ['#4caf50', '#f44336'],
                                    hoverBackgroundColor: ['#66bb6a', '#e57373']
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                let value = context.raw;
                                                let percentage = ((value / total) * 100).toFixed(2);
                                                return `${context.label}: ${percentage}% (${value})`;
                                            }
                                        }
                                    }
                                }
                            }
                        });

                        // Bar Chart
                        const labels = self.collabs.map(collaborator => {
                            let name = collaborator.name || collaborator.login_name;
                            return collaborator.university_username ? name + ' (' + collaborator.university_username + ')' : name;
                        });
                        const meaningfulData = self.collabs.map(collaborator => {
                            let total = collaborator.meaningful_commit_files_count + collaborator.not_meaningful_commit_files_count;
                            return (total > 0) ? ((collaborator.meaningful_commit_files_count / total) * 100).toFixed(2) : 0;
                        });
                        const notMeaningfulData = self.collabs.map(collaborator => {
                            let total = collaborator.meaningful_commit_files_count + collaborator.not_meaningful_commit_files_count;
                            return (total > 0) ? ((collaborator.not_meaningful_commit_files_count / total) * 100).toFixed(2) : 0;
                        });

                        const barCtx = document.getElementById('commitFilesBarChart').getContext('2d');
                        this.barChart = new Chart(barCtx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Meaningful Commit Files',
                                        data: meaningfulData,
                                        backgroundColor: '#4caf50',
                                        hoverBackgroundColor: '#66bb6a'
                                    },
                                    {
                                        label: 'Not Meaningful Commit Files',
                                        data: notMeaningfulData,
                                        backgroundColor: '#f44336',
                                        hoverBackgroundColor: '#e57373'
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        stacked: true,
                                    },
                                    y: {
                                        stacked: true,
                                        ticks: {
                                            callback: function (value) {
                                                return value + '%'; // add % sign to the y-axis labels
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                let value = context.raw;
                                                return `${context.dataset.label}: ${value}%`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    },
                    showModal(collaborator) {
                        self.UpdateCollaborator.githubUsername = collaborator.login_name;
                        self.UpdateCollaborator.githubId = collaborator.git_id;
                        self.UpdateCollaborator.avatarUrl = collaborator.avatar_url;
                        self.UpdateCollaborator.name = collaborator.name;
                        self.UpdateCollaborator.university_username = collaborator.university_username;
                        self.UpdateCollaborator.status = collaborator.status;
                        const modalElement = document.getElementById('basicModal');
                        if (modalElement) {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        }
                    },
                },
                mounted() {
                    self = this;
                    self.fetchRepoInfo();
                }
            });
        });
    </script>

@endsection
