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
    </style>
    <div id="repositoryInfo" >
        <div class="card mb-4 shadow-sm" style="background:#303156;">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center">
                    <h5 class="card-title d-flex align-items-center me-4 mt-2 m-lg-2" style="color: #ffb8d7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                            <path d="M21.993 7.95a.96.96 0 0 0-.029-.214c-.007-.025-.021-.049-.03-.074-.021-.057-.04-.113-.07-.165-.016-.027-.038-.049-.057-.075-.032-.045-.063-.091-.102-.13-.023-.022-.053-.04-.078-.061-.039-.032-.075-.067-.12-.094-.004-.003-.009-.003-.014-.006l-.008-.006-8.979-4.99a1.002 1.002 0 0 0-.97-.001l-9.021 4.99c-.003.003-.006.007-.011.01l-.01.004c-.035.02-.061.049-.094.073-.036.027-.074.051-.106.082-.03.031-.053.067-.079.102-.027.035-.057.066-.079.104-.026.043-.04.092-.059.139-.014.033-.032.064-.041.1a.975.975 0 0 0-.029.21c-.001.017-.007.032-.007.05V16c0 .363.197.698.515.874l8.978 4.987.001.001.002.001.02.011c.043.024.09.037.135.054.032.013.063.03.097.039a1.013 1.013 0 0 0 .506 0c.033-.009.064-.026.097-.039.045-.017.092-.029.135-.054l.02-.011.002-.001.001-.001 8.978-4.987c.316-.176.513-.511.513-.874V7.998c0-.017-.006-.031-.007-.048zm-10.021 3.922L5.058 8.005 7.82 6.477l6.834 3.905-2.682 1.49zm.048-7.719L18.941 8l-2.244 1.247-6.83-3.903 2.153-1.191zM13 19.301l.002-5.679L16 11.944V15l2-1v-3.175l2-1.119v5.705l-7 3.89z"></path>
                        </svg>
                        <span class="ms-2">Repository Name: <span style="color: #ffffff">@{{repositoryInfo.repositoryName}}</span></span>
                    </h5>
                    <h5 class="card-title d-flex align-items-center mt-2 m-lg-2" style="color: #ffb8d7;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                            <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3zm9 11v-1a7 7 0 0 0-7-7h-4a7 7 0 0 0-7 7v1h2v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1z"></path>
                        </svg>
                        <span class="ms-2">Repository Owner: <span style="color: #ffffff">@{{repositoryInfo.repositoryOwner}}</span></span>
                    </h5>

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
                            <button
                                class="btn p-0"
                                type="button"
                                id="orderStatistics"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex flex-column align-items-center gap-1">
                                <h2 class="mb-2" v-html="collabs.length"></h2>
                                <span>Info</span>
                            </div>
                        </div>
                        <ul class="p-0 m-0" v-for="collaborator in collabs">
                            <li class="d-flex mb-4 pb-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img :src="collaborator.avatar_url" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <a :href="collaborator.github_url" target="_blank" v-if="collaborator.name !== ''"><h6 class="mb-0">@{{collaborator.name}}</h6></a>
                                        <a :href="collaborator.github_url" target="_blank" v-else-if="collaborator.login_name !== ''"><h6 class="mb-0">@{{collaborator.login_name}}</h6></a>
                                    </div>
                                    <div class="user-progress">
                                        <small class="fw-semibold" style="color: #5f61e6">@{{collaborator.commit_count}} commits</small>
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
                        <span class="d-block mb-1">Total Commits <a :href="repositoryInfo.commitDashboardUrl" class="align-content-center"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(10, 65, 255, 1);transform: ;msFilter:;"><path d="M8.465 11.293c1.133-1.133 3.109-1.133 4.242 0l.707.707 1.414-1.414-.707-.707c-.943-.944-2.199-1.465-3.535-1.465s-2.592.521-3.535 1.465L4.929 12a5.008 5.008 0 0 0 0 7.071 4.983 4.983 0 0 0 3.535 1.462A4.982 4.982 0 0 0 12 19.071l.707-.707-1.414-1.414-.707.707a3.007 3.007 0 0 1-4.243 0 3.005 3.005 0 0 1 0-4.243l2.122-2.121z"></path><path d="m12 4.929-.707.707 1.414 1.414.707-.707a3.007 3.007 0 0 1 4.243 0 3.005 3.005 0 0 1 0 4.243l-2.122 2.121c-1.133 1.133-3.109 1.133-4.242 0L10.586 12l-1.414 1.414.707.707c.943.944 2.199 1.465 3.535 1.465s2.592-.521 3.535-1.465L19.071 12a5.008 5.008 0 0 0 0-7.071 5.006 5.006 0 0 0-7.071 0z"></path></svg><path d="M8.465 11.293c1.133-1.133 3.109-1.133 4.242 0l.707.707 1.414-1.414-.707-.707c-.943-.944-2.199-1.465-3.535-1.465s-2.592.521-3.535 1.465L4.929 12a5.008 5.008 0 0 0 0 7.071 4.983 4.983 0 0 0 3.535 1.462A4.982 4.982 0 0 0 12 19.071l.707-.707-1.414-1.414-.707.707a3.007 3.007 0 0 1-4.243 0 3.005 3.005 0 0 1 0-4.243l2.122-2.121z"></path><path d="m12 4.929-.707.707 1.414 1.414.707-.707a3.007 3.007 0 0 1 4.243 0 3.005 3.005 0 0 1 0 4.243l-2.122 2.121c-1.133 1.133-3.109 1.133-4.242 0L10.586 12l-1.414 1.414.707.707c.943.944 2.199 1.465 3.535 1.465s2.592-.521 3.535-1.465L19.071 12a5.008 5.008 0 0 0 0-7.071 5.006 5.006 0 0 0-7.071 0z"></path></svg></a></span>
                        <h3 class="text-primary card-title text-nowrap mb-2">@{{repositoryInfo.commitsCount}}</h3>
                        <span class="d-block mb-1">First Commit</span>
                        <span class="text-primary card-title text-nowrap mb-2">@{{repositoryInfo.firstCommit}}</span>

                        <span class="d-block mb-1">Last Commit</span>
                        <span class="text-primary card-title text-nowrap mb-2">@{{repositoryInfo.lastCommit}}</span>
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
                                <button
                                    class="btn p-0"
                                    type="button"
                                    id="cardOpt4"
                                    data-bs-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                >
                                </button>
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
                        <h5 class="card-title">Commit Files Distribution by User</h5>
                        <div class="chart-container">
                            <canvas id="commitFilesBarChart"></canvas>
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
                    collabs:[],
                    repositoryInfo:{
                      repositoryName:'',
                      repositoryOwner:'',
                      commitsCount:0,
                      commitsFilesCount:0,
                      repositoryUrl:'',
                      commitDashboardUrl:'',
                      repositoryMeaningFullCommitCount:0,
                      repositoryNotMeaningFullCommitCount:0,
                      lastCommit:'',
                      firstCommit:''
                    },
                    fetchRepoInfoUrl: '{{ route("repository.info", ["repoId" => ":repoId"]) }}'
                },
                methods: {
                    fetchRepoInfo() {
                        self = this
                        const repoId = window.location.pathname.split('/').pop();
                        const url = self.fetchRepoInfoUrl.replace(':repoId', repoId);
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                self.collabs = data.data.collabs;
                                self.repositoryInfo.commitsCount= data.data.commitsCount;
                                self.repositoryInfo.commitsFilesCount = data.data.commitsFilesCount;
                                self.repositoryInfo.repositoryName= data.data.name;
                                self.repositoryInfo.repositoryOwner= data.data.owner;
                                self.repositoryInfo.repositoryUrl = data.data.repositoryUrl;
                                self.repositoryInfo.repositoryMeaningFullCommitCount = data.data.meaningfulCommitFilesCount;
                                self.repositoryInfo.repositoryNotMeaningFullCommitCount = data.data.NotMeaningfulCommitFilesCount;
                                self.repositoryInfo.lastCommit = data.data.firstCommit;
                                self.repositoryInfo.firstCommit = data.data.lastCommit;
                                self.repositoryInfo.commitDashboardUrl = data.data.commitDashboardUrl;
                                self.loading = false;
                                self.$nextTick(() => {
                                    self.renderChart();
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching repository info:', error);
                                self.loading = false
                            });
                    },
                    renderChart() {
                        self = this
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
                                            label: function(context) {
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
                        const labels = self.collabs.map(collaborator => collaborator.name || collaborator.login_name);
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
                                            callback: function(value) {
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
                                            label: function(context) {
                                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                let value = context.raw;
                                                return `${context.dataset.label}: ${value}%`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                },
                mounted() {
                    self = this;
                    self.fetchRepoInfo();
                }
            });
        });
    </script>
@endsection
