<?php
namespace axonivy\update\controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use axonivy\update\model\Java;
use axonivy\update\model\Designer;
use axonivy\update\model\Engine;
use axonivy\update\model\Licence;
use axonivy\update\model\Memory;
use axonivy\update\model\Network;
use axonivy\update\model\OperatingSystem;
use axonivy\update\model\SystemDatabase;

use axonivy\update\repository\DesignerLogRepository;
use axonivy\update\repository\EngineLogRepository;
use axonivy\update\model\DesignerLogRecord;
use axonivy\update\model\EngineLogRecord;
use axonivy\update\repository\ReleaseInfoRepository;
use axonivy\update\model\HttpRequest;

class CallingHomeController
{

    private DesignerLogRepository $designerLogRepo;

    private EngineLogRepository $engineLogRepo;
    
    private ReleaseInfoRepository $releaseInfoRepo;

    private $request;
    
    public function __construct(DesignerLogRepository $designerLogRepo, EngineLogRepository $engineLogRepo, ReleaseInfoRepository $releaseInfoRepo)
    {
        $this->designerLogRepo = $designerLogRepo;
        $this->engineLogRepo = $engineLogRepo;
        $this->releaseInfoRepo = $releaseInfoRepo;
    }

    public function designer(Request $request, Response $response)
    {
        $this->request = $request->withParsedBody($_POST);
        $record = $this->createDesignerLogRecord();
        $this->designerLogRepo->write($record);
        return $this->render($response, $record->getDesigner()->getVersion());
    }
    
    private function createDesignerLogRecord(): DesignerLogRecord
    {
        $timestamp = time();
        $httpRequest = $this->createHttpRequest();
        $java = $this->createJava();
        $designer = $this->createDesigner();
        $memory = $this->createMemory();
        $network = $this->createNetwork();
        $operatingSystem = $this->createOperatingSystem();
        
        return new DesignerLogRecord($timestamp, $httpRequest, $java, $designer, $memory, $network, $operatingSystem);
    }
    
    public function engine(Request $request, Response $response)
    {
        $this->request = $request->withParsedBody($_POST);
        $record = $this->createEngineLogRecord();
        $this->engineLogRepo->write($record);
        return $this->render($response, $record->getEngine()->getVersion());
    }
    
    private function render(Response $response, $currentRelease)
    {
        $latestReleaseVersion = '';
        $latestServiceReleaseVersion = '';
        
        try {
            $info = $this->releaseInfoRepo->getCurrentReleaseInfo($currentRelease);
            $latestReleaseVersion = $info->latestReleaseVersion;
            $latestServiceReleaseVersion = $info->latestServiceReleaseVersion;
        } catch (\Exception $ex) {
            $latestReleaseVersion = '0.0.0';
            $latestServiceReleaseVersion = '0.0.0';
        }

        $downloadUrl = 'https://developer.axonivy.com/download/';
        
        $body = $response->getBody();
        $body->write("\n");
        $body->write("\n");
        $body->write('LatestRelease: ' . $latestReleaseVersion);
        $body->write("\n");
        $body->write('LatestReleaseUrl: ' . $downloadUrl);
        $body->write("\n");
        $body->write('LatestServiceReleaseForCurrentRelease: ' . $latestServiceReleaseVersion);
        $body->write("\n");
        $body->write('LatestServiceReleaseForCurrentReleaseUrl: ' . $downloadUrl);
        return $response->withAddedHeader('Expires', 0);
    }
    
    private function createEngineLogRecord(): EngineLogRecord
    {
        $timestamp = time();
        $httpRequest = $this->createHttpRequest();
        $java = $this->createJava();
        $licence = $this->createLicence();
        $memory = $this->createMemory();
        $network = $this->createNetwork();
        $operatingSystem = $this->createOperatingSystem();
        $systemDatabase = $this->createSystemDatabase();
        $engine = $this->createEngine();
        
        return new EngineLogRecord($timestamp, $httpRequest, $java, $licence, $memory, $network, $operatingSystem, $engine, $systemDatabase);
    }
    
    private function createHttpRequest(): HttpRequest
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? ''; // or use a middleware to get the ip
        return new HttpRequest($ipAddress);
    }
    
    private function createJava(): Java
    {
        $vendor = $this->post('JavaVendor');
        $version = $this->post('JavaVersion');
        $vmName = $this->post('JavaVirtualMachineName');
        $vmVendor = $this->post('JavaVirtualMachineVendor');
        $vmVersion = $this->post('JavaVirtualMachineVersion');
        
        return new Java($vendor, $version, $vmName, $vmVendor, $vmVersion);
    }
    
    private function createLicence(): Licence
    {
        $id = $this->post('LicenceNumber');
        $individual = $this->post('LicenceeIndividual');
        $organisation = $this->post('LicenceeOrganisation');
        
        return new Licence($id, $individual, $organisation);
    }
    
    private function createDesigner(): Designer
    {
        $version = $this->post('DesignerVersion');
        return new Designer($version);
    }
    
    private function createEngine(): Engine
    {
        $applications = $this->post('ServerApplications');
        $clusterNodesConfigured = $this->post('ServerClusterNodesConfigured');
        $clusterNodesRunning = $this->post('ServerClusterNodesRunning');
        $licensedUsers = $this->post('ServerLicensedUsers');
        $processModelVersions = $this->post('ServerProcessModelVersions');
        $processModelVersionsDeleted = $this->post('ServerProcessModelVersionsDeleted');
        $processModels = $this->post('ServerProcessModels');
        $runningCases = $this->post('ServerRunningCases');
        $runningTasks = $this->post('ServerRunningTasks');
        $upTime = $this->post('ServerUpTime');
        $users = $this->post('ServerUsers');
        $version = $this->post('ServerVersion');
        
        return new Engine($applications, $clusterNodesConfigured, $clusterNodesRunning, $licensedUsers, $processModelVersions, $processModelVersionsDeleted, $processModels, $runningCases, $runningTasks, $upTime, $users, $version);
    }
    
    private function createMemory(): Memory
    {
        $maxHeapMemory = $this->post('MemoryMaxHeap');
        $maxNonHeapMemory = $this->post('MemoryMaxNonHeap');
        
        return new Memory($maxHeapMemory, $maxNonHeapMemory);
    }
    
    private function createNetwork(): Network
    {
        $hostName = $this->post('NetworkHostName');
        $ipAddress = $this->post('NetworkIpAddress');
        $hardwareAddress = $this->post('NetworkHardwareAddress');
        
        return new Network($hardwareAddress, $ipAddress, $hostName);
    }
    
    private function createOperatingSystem(): OperatingSystem
    {
        $architecture = $this->post('OperatingSystemArchitecture');
        $name = $this->post('OperatingSystemName');
        $version = $this->post('OperatingSystemVersion');
        $availableProcessors = $this->post('OperatingSystemAvailableProcessors');
        
        return new OperatingSystem($architecture, $name, $version, $availableProcessors);
    }
    
    private function createSystemDatabase(): SystemDatabase
    {
        $id = $this->post('SystemDatabaseId');
        $driver = $this->post('SystemDatabaseDriver');
        $productName = $this->post('SystemDatabaseProductName');
        $productVersion = substr($this->post('SystemDatabaseProductVersion'), 0, 50);
        
        return new SystemDatabase($id, $driver, $productName, $productVersion);
    }

    private function post($variable)
    {
        $parsedBody = $this->request->getParsedBody();
        if (isset($parsedBody[$variable])) {
            return $parsedBody[$variable];
        }
        return '';
    }
}