<?php

namespace Config;

use App\Modules\Recruitment\Models\ScreeningQuestionModel;
use App\Modules\Recruitment\Models\DepartmentModel;
use App\Modules\Recruitment\Models\VacancyModel;
use App\Modules\Recruitment\Models\ApplicantModel;
use App\Modules\Recruitment\Models\ApplicationModel;
use App\Modules\Recruitment\Models\ScreeningAnswerModel;
use App\Modules\Recruitment\Models\ApplicationBatchModel;
use App\Modules\Recruitment\Models\ApplicantDocumentModel;
use App\Modules\Recruitment\Presenters\VacancyPresenter;
use App\Modules\Recruitment\Services\ApplicationSubmissionService;
use App\Modules\Recruitment\Services\VacancyCatalogService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function vacancyCatalog(bool $getShared = true): VacancyCatalogService
    {
        if ($getShared) {
            return static::getSharedInstance('vacancyCatalog');
        }

        return new VacancyCatalogService(
            new VacancyModel(),
            new ScreeningQuestionModel(),
            new DepartmentModel(),
            new VacancyPresenter(),
        );
    }

    public static function applicationSubmission(bool $getShared = true): ApplicationSubmissionService
    {
        if ($getShared) {
            return static::getSharedInstance('applicationSubmission');
        }

        return new ApplicationSubmissionService(
            db_connect(),
            new ApplicantModel(),
            new ApplicationModel(),
            new ScreeningAnswerModel(),
            new ApplicationBatchModel(),
            new ApplicantDocumentModel(),
        );
    }

    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */
}
