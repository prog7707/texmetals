<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Upgrade\Test\TestCase;

use Magento\Mtf\TestCase\Injectable;
use Magento\Upgrade\Test\Page\Adminhtml\SetupWizard;
use Magento\Backend\Test\Page\Adminhtml\Dashboard;
use Magento\Mtf\Fixture\FixtureFactory;
use Magento\Upgrade\Test\Constraint\AssertSuccessfulReadinessCheck;
use Magento\Upgrade\Test\Constraint\AssertVersionAndEditionCheck;
use Magento\Upgrade\Test\Constraint\AssertSuccessMessage;
use Magento\Upgrade\Test\Constraint\AssertApplicationVersion;

class UpgradeSystemTest extends Injectable
{
    /**
     * Page System Upgrade Index.
     *
     * @var SetupWizard
     */
    protected $setupWizard;

    /**
     * @var Dashboard
     */
    protected $adminDashboard;

    /**
     * Injection data.
     *
     * @param Dashboard $adminDashboard
     * @param SetupWizard $setupWizard
     * @return void
     */
    public function __inject(
        Dashboard $adminDashboard,
        SetupWizard $setupWizard
    ) {
        $this->adminDashboard = $adminDashboard;
        $this->setupWizard = $setupWizard;
    }

    /**
     * @param FixtureFactory $fixtureFactory
     * @param AssertSuccessfulReadinessCheck $assertReadiness
     * @param AssertVersionAndEditionCheck $assertVersionAndEdition
     * @param AssertSuccessMessage $assertSuccessMessage
     * @param AssertApplicationVersion $assertApplicationVersion
     * @param array $upgrade
     * @return void
     */
    public function test(
        FixtureFactory $fixtureFactory,
        AssertSuccessfulReadinessCheck $assertReadiness,
        AssertVersionAndEditionCheck $assertVersionAndEdition,
        AssertSuccessMessage $assertSuccessMessage,
        AssertApplicationVersion $assertApplicationVersion,
        $upgrade = []
    ) {
        // Create fixture
        $upgradeFixture = $fixtureFactory->create('Magento\Upgrade\Test\Fixture\Upgrade', ['data' => $upgrade]);
        $createBackupConfig = array_intersect_key(
            $upgrade,
            ['optionsCode' => '', 'optionsMedia' => '', 'optionsDb' => '']
        );
        $createBackupFixture = $fixtureFactory->create(
            'Magento\Upgrade\Test\Fixture\Upgrade',
            ['data' => $createBackupConfig]
        );
        $version = $upgrade['upgradeVersion'];
        $sampleDataVersion = $upgrade['sampledataVersion'];

        $suffix = "( (CE|EE))$";
        $normalVersion = '(0|[1-9]\d*)';
        $preReleaseVersion = "((0(?!\\d+(\\.|\\+|{$suffix}))|[1-9A-Za-z])[0-9A-Za-z-]*)";
        $buildVersion = '([0-9A-Za-z][0-9A-Za-z-]*)';
        $versionPattern = "/^{$normalVersion}(\\.{$normalVersion}){2}"
            . "(-{$preReleaseVersion}(\\.{$preReleaseVersion})*)?"
            . "(\\+{$buildVersion}(\\.{$buildVersion})*)?{$suffix}/";

        if (preg_match($versionPattern, $version)) {
            preg_match("/(.*){$suffix}/", $version, $matches);
            $version = $matches[1];
        } else {
            $this->fail(
                "Provided version format does not comply with semantic versioning specification. Got '{$version}'"
            );
        }

        // Authenticate in admin area
        $this->adminDashboard->open();

        // Open Web Setup Wizard
        $this->setupWizard->open();

        // Authenticate on repo.magento.com
        if ($upgrade['needAuthentication'] === 'Yes') {
            $this->setupWizard->getSystemConfig()->clickSystemConfig();
            $this->setupWizard->getAuthentication()->fill($upgradeFixture);
            $this->setupWizard->getAuthentication()->clickSaveConfig();
            $this->setupWizard->open();
        }

        // Select upgrade to version
        $this->setupWizard->getSystemUpgradeHome()->clickSystemUpgrade();
        $this->setupWizard->getSelectVersion()->fill($upgradeFixture);
        if ($upgrade['otherComponents'] === 'Yes') {
            $this->setupWizard->getSelectVersion()->chooseUpgradeOtherComponents();
            $this->setupWizard->getSelectVersion()->chooseVersionUpgradeOtherComponents($sampleDataVersion);
        }
        $this->setupWizard->getSelectVersion()->clickNext();

        // Readiness Check
        $this->setupWizard->getReadiness()->clickReadinessCheck();
        $assertReadiness->processAssert($this->setupWizard);
        $this->setupWizard->getReadiness()->clickNext();

        // Create Backup page
        $this->setupWizard->getCreateBackup()->fill($createBackupFixture);
        $this->setupWizard->getCreateBackup()->clickNext();

        // Check info and press 'Upgrade' button
        $assertVersionAndEdition->processAssert($this->setupWizard, $upgrade['package'], $version);
        $this->setupWizard->getSystemUpgrade()->clickSystemUpgrade();

        $assertSuccessMessage->processAssert($this->setupWizard, $upgrade['package']);

        // Check application version
        $this->adminDashboard->open();
        $assertApplicationVersion->processAssert($this->adminDashboard, $version);
    }
}
