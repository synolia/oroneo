#!/usr/bin/env groovy

def binDir              = "/var/lib/jenkins/.composer/vendor/bin"
def sourceDir           = "."
def buildDir            = "build"
def pathToPhpMdXml      = "phpmd.xml"
def pathToPhpUnitXml    = "phpunit.xml.dist"
def pathToPhpcsStandard = "/var/lib/jenkins/.composer/vendor/escapestudios/symfony2-coding-standard"

node {
    try {
        stage('Checkout & Cleaning') {
            notifySlack("#FFFF00", "\u27A1 STARTED : Build <${env.BUILD_URL}|#${env.BUILD_NUMBER}> >> ${env.JOB_NAME}.")
            checkout scm

            sh 'rm -rf build/'
            sh 'rm -rf vendor'
            sh 'mkdir build/'
            sh 'mkdir build/logs/'
            sh "mkdir ${buildDir}/api ${buildDir}/code-browser ${buildDir}/coverage ${buildDir}/pdepend"
        }

        stage('Composer Installation') {
            //echo "Composer folder remove"
            //sh 'rm -rf /var/lib/jenkins/.composer/vendor'
            //sh 'rm -rf /var/lib/jenkins/.composer/composer.lock'
            //sh 'rm -rf /var/lib/jenkins/.composer/composer.json'

            echo "Composer installation"
            sh 'curl -s https://getcomposer.org/installer | php'

            withCredentials([string(credentialsId: '36cee661-cc61-4667-a930-fa1923beaf75', variable: 'githubToken')]) {
                sh 'php composer.phar config -g github-oauth.github.com ${githubToken}'
            }

            sh 'php composer.phar clearcache'
            sh 'php composer.phar global require hirak/prestissimo ^0.3'
            sh 'php composer.phar global require fxp/composer-asset-plugin:1.2.2'
            sh "php composer.phar global require phpunit/phpunit 4.8.*"
            sh "php composer.phar global require phpmd/phpmd 2.5.0"
            sh "php composer.phar global require squizlabs/php_codesniffer 2.7.1"
            sh "php composer.phar global require sebastian/phpcpd 2.*"
            sh "php composer.phar global require phploc/phploc 3.*"
            sh "php composer.phar global require pdepend/pdepend 2.3.2"
            sh "php composer.phar global require escapestudios/symfony2-coding-standard 2.9.1"
            sh "php composer.phar global require mayflower/php-codebrowser 1.1.1 --no-update"
            sh "php composer.phar global update"
        }

        stage('Application Installation') {
            //branch name from Jenkins environment variables
            echo "Building branch ${env.BRANCH_NAME}"

            withCredentials([usernamePassword(credentialsId: 'f7b8694e-416c-486f-ab5f-0111579140f8', passwordVariable: 'token', usernameVariable: 'consumerKey')]) {
                sh "php composer.phar config bitbucket-oauth.bitbucket.org ${consumerKey} ${token}"
            }

            echo "Composer install"
            wrap([$class: 'AnsiColorBuildWrapper', 'colorMapName': 'XTerm']) {
                sh 'php composer.phar install --prefer-dist --no-interaction --no-progress --optimize-autoloader'
                //sh 'php composer.phar update --no-scripts --optimize-autoloader --no-interaction --no-progress --prefer-dist'
            }
            echo 'Install finished'
        }

        if (!env.BRANCH_NAME.startsWith("PR-")) {
            stage('Static Analysis') {
                //TODO : add php lint
                //TODO : add phpdoc
                parallel 'PHP Mess Detector': {
                    def workspace = pwd()
                    try {
                        //sh 'docker run --rm -v jenkins2docker_data:/var/jenkins_home -w ${PWD} willoucom/php-multitest phpmd src html cleancode --reportfile ci/logs/phpmd.html'
                        sh "${binDir}/phpmd ${workspace} xml ${pathToPhpMdXml} --reportfile ${buildDir}/logs/pmd.xml --exclude vendor --ignore-violations-on-exit"
                    } finally {
                        step([$class: 'PmdPublisher', pattern: "${buildDir}/logs/pmd.xml", canRunOnFailed: true])
                    }
                },
                'PHP Code Sniffer': {
                    def workspace = pwd()
                    try {
                        //sh 'docker run --rm -v jenkins2docker_data:/var/jenkins_home -w ${PWD} willoucom/php-multitest phpcs --report=checkstyle --report-file=ci/logs/phpcs.xml --standard=PSR2 --extensions=php --ignore=autoload.php src'
                        sh "${binDir}/phpcs --config-set installed_paths ${pathToPhpcsStandard}"
                        sh "${binDir}/phpcs --config-set ignore_errors_on_exit 1"
                        //sh "${binDir}/phpcs --report=checkstyle --report-file=${buildDir}/logs/checkstyle.xml --extensions=php --standard=Symfony2 --ignore=vendor ."
                        sh "${binDir}/phpcs --report=checkstyle --ignore=vendor/*,Tests/* --report-file=${workspace}/build/logs/checkstyle.xml --standard=Symfony2 --extensions=php ."
                    } finally {
                        step([$class: 'CheckStylePublisher', pattern: "${buildDir}/logs/checkstyle.xml", canRunOnFailed: true])
                    }
                },
                'PHP Copy/Paste Detector': {
                    def workspace = pwd()
                    try {
                        //sh 'docker run --rm -v jenkins2docker_data:/var/jenkins_home -w ${PWD} willoucom/php-multitest phpcpd --log-pmd ci/logs/phpcpd.xml src'
                        sh "${binDir}/phpcpd --log-pmd ${buildDir}/logs/pmd-cpd.xml --exclude=vendor ${workspace}"
                    } finally {
                        step([$class: 'DryPublisher', canComputeNew: false, defaultEncoding: '', healthy: '', pattern: "${buildDir}/logs/pmd-cpd.xml", unHealthy: ''])
                    }
                },
                'PHPLoc': {
                    def workspace = pwd()
                    //sh 'docker run --rm -v jenkins2docker_data:/var/jenkins_home -w ${PWD} willoucom/php-multitest php /mageekguy.atoum.phar -c ci/atoum.conf.php -d tests/units'
                    sh "${binDir}/phploc --log-csv ${buildDir}/logs/phploc.csv --exclude vendor ${workspace} || true"
                },
                'PDepend': {
                    def workspace = pwd()
                    //TODO : check if svg are used
                    sh "${binDir}/pdepend --jdepend-xml=${buildDir}/logs/jdepend.xml --jdepend-chart=${buildDir}/pdepend/dependencies.svg --overview-pyramid=${buildDir}/pdepend/overview-pyramid.svg --ignore=vendor,Tests ${workspace} || true"
                },
                'PHP Code Browser': {
                    def workspace = pwd()
                    try {
                        sh "${binDir}/phpcb --log ${buildDir}/logs --ignore=vendor --source ${workspace} --output ${buildDir}/code-browser"
                    } finally {
                        publishHTML([allowMissing: false, alwaysLinkToLastBuild: false, keepAll: false, reportDir: "${buildDir}/code-browser", reportFiles: 'index.html', reportName: 'Code browser'])
                    }
                }

                echo 'Static analysis finished'
            }
        }

        stage('Tests') {
            try {
                def workspace = pwd()
                //sh "vendor/phpunit/phpunit/phpunit --log-junit ${buildDir}/logs/junit.xml --coverage-html=reports/clover --coverage-xml=${buildDir}/coverage ${sourceDir}/Tests"
                sh "${binDir}/phpunit --log-junit ${buildDir}/logs/junit.xml Tests/Unit/ --bootstrap vendor/autoload.php"
            } finally {
                junit "${buildDir}/logs/junit.xml"
                publishHTML([allowMissing: true, alwaysLinkToLastBuild: false, keepAll: false, reportDir: "${buildDir}/coverage", reportFiles: 'index.html', reportName: 'Coverage'])
            }
        }
        notifySlack('#00FF00', "\u2705 SUCCESSFUL : Build <${env.BUILD_URL}|#${env.BUILD_NUMBER}> >> ${env.JOB_NAME}. :aussie_parrot: :tada: :medal:")
    } catch (e) {
        notifySlack('#FF0000', ":middle_finger: FAILED : Build <${env.BUILD_URL}|#${env.BUILD_NUMBER}> >> ${env.JOB_NAME}.")
        throw e
    } finally {
        sh "rm -rf *"
    }
}

def notifySlack(String color, String message) {
   slackSend channel: '@djamy', color: "${color}", message: "${message}", teamDomain: 'synolia', tokenCredentialId: '8514546a-d1ba-4ccf-9fcc-da8ee305f39a'
}
