<?php

namespace Modules\Repository\src\Enumerations;

class RepositoryResponseEnums
{
    const REPOSITORY_NOT_FOUND = "Repository not found!";
    const REPOSITORY_CREATION_FAILED = "Failed to create repository.";
    const REPOSITORY_RETRIEVAL_FAILED = "Failed to retrieve repositories.";
    const REPOSITORY_UPDATE_FAILED = "Failed to create repository.";
    const REPOSITORY_RETRIEVAL_WITH_COMMIT_FAILED = "Failed to retrieve repository with commits.";

    const ACCESS_FAILED_FOR_CREATING_REPOSITORY_UNDER_ORGANIZATION = 'You do not have permission to create repositories in this organization.';
    const REPOSITORY_INFO_FIND_FAILED = 'Failed to find repository info.';
    const REPOSITORY_DELETE_FAILED = 'Failed to delete repository.';
}
