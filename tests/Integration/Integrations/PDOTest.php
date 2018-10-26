<?php

namespace DDTrace\Tests\Integration\Integrations;

use DDTrace\Integrations\PDO;
use DDTrace\Tests\Integration\Common\IntegrationTestCase;
use DDTrace\Tests\Integration\Common\SpanAssertion;

define('MYSQL_DATABASE', 'test');
define('MYSQL_USER', 'test');
define('MYSQL_PASSWORD', 'test');
define('MYSQL_HOST', 'mysql_integration');


final class PDOTest extends IntegrationTestCase
{
    public static function setUpBeforeClass()
    {
        PDO::load();
    }

    protected function setUp()
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown()
    {
        $this->clearDatabase();
        parent::tearDown();
    }

    public function testPDOContructOk()
    {
        $traces = $this->withTracer(function () {
                $this->pdoInstance();
        });
        $this->assertSpans($traces, [
            SpanAssertion::build('PDO.__construct', 'PDO', 'sql', 'PDO.__construct')
                ->withExactTags([]),
        ]);
    }

    public function testPDOContructError()
    {
        $traces = $this->withTracer(function () {
            try {
                new \PDO($this->mysqlDns(), 'wrong_user', 'wrong_password');
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::build('PDO.__construct', 'PDO', 'sql', 'PDO.__construct')
                ->setError()
                ->withExactTags([
                    'error.type' => 'PDOException',
                    'error.msg' => 'Sql error: SQLSTATE[HY000] [1045]',
                ]),
        ]);
    }

    public function testPDOExecOk()
    {
        $query = "INSERT INTO tests (id, name) VALUES (100, 'Sam')";
        $traces = $this->withTracer(function () use ($query) {
            $pdo = $this->pdoInstance();
            $pdo->beginTransaction();
            $pdo->exec($query);
            $pdo->commit();
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.exec', 'PDO', 'sql', $query)
                ->withExactTags(array_merge($this->baseTags(), [
                    'db.rowcount' => '1',
                ])),
            SpanAssertion::exists('PDO.commit'),
        ]);
    }

    public function testPDOExecError()
    {
        $query = "WRONG QUERY)";
        $traces = $this->withTracer(function () use ($query) {
            try {
                $pdo = $this->pdoInstance();
                $pdo->beginTransaction();
                $pdo->exec($query);
                $pdo->commit();
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.exec', 'PDO', 'sql', $query)
                ->setError()
                ->withExactTags(array_merge($this->baseTags(), [
                    'db.rowcount' => '',
                    'error.msg' => 'SQL error: 42000. Driver error: 1064',
                    'error.type' => 'PDO error',
                ])),
            SpanAssertion::exists('PDO.commit'),
        ]);
    }

    public function testPDOExecException()
    {
        $query = "WRONG QUERY)";
        $traces = $this->withTracer(function () use ($query) {
            try {
                $pdo = $this->pdoInstance();
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->beginTransaction();
                $pdo->exec($query);
                $pdo->commit();
                $this->fail('Should throw and exception');
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.exec', 'PDO', 'sql', $query)
                ->setError()
                ->withExactTags(array_merge($this->baseTags(), [
                    'error.msg' => 'Sql error',
                    'error.type' => 'PDOException',
                ])),
        ]);
    }

    public function testPDOQuery()
    {
        $query = "SELECT * FROM tests WHERE id=1";
        $traces = $this->withTracer(function () use ($query) {
            $pdo = $this->pdoInstance();
            $pdo->query($query);
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.query', 'PDO', 'sql', $query)
                ->withExactTags(array_merge($this->baseTags(), [
                    'db.rowcount' => '1',
                ])),
        ]);
    }

    public function testPDOQueryError()
    {
        $query = "WRONG QUERY";
        $traces = $this->withTracer(function () use ($query) {
            try {
                $pdo = $this->pdoInstance();
                $pdo->query($query);
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.query', 'PDO', 'sql', $query)
                ->setError()
                ->withExactTags(array_merge($this->baseTags(), [
                    'db.rowcount' => '',
                    'error.msg' => 'SQL error: 42000. Driver error: 1064',
                    'error.type' => 'PDO error',
                ])),
        ]);
    }

    public function testPDOQueryException()
    {
        $query = "WRONG QUERY";
        $traces = $this->withTracer(function () use ($query) {
            try {
                $pdo = $this->pdoInstance();
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->query($query);
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.query', 'PDO', 'sql', $query)
                ->setError()
                ->withExactTags(array_merge($this->baseTags(), [
                    'error.msg' => 'Sql error',
                    'error.type' => 'PDOException',
                ])),
        ]);
    }

    public function testPDOCommit()
    {
        $query = "INSERT INTO tests (id, name) VALUES (100, 'Sam')";
        $traces = $this->withTracer(function () use ($query) {
            $pdo = $this->pdoInstance();
            $pdo->beginTransaction();
            $pdo->exec($query);
            $pdo->commit();
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::exists('PDO.exec'),
            SpanAssertion::build('PDO.commit', 'PDO', 'sql', 'PDO.commit')
                ->withExactTags(array_merge($this->baseTags(), [])),
        ]);
    }

    public function testPDOStatementOk()
    {
        $query = "SELECT * FROM tests WHERE id = ?";
        $traces = $this->withTracer(function () use ($query) {
            $pdo = $this->pdoInstance();
            $stmt = $pdo->prepare($query);
            $stmt->execute([1]);
            $results = $stmt->fetchAll();
            $this->assertEquals('Tom', $results[0]['name']);
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build(
                'PDO.prepare',
                'PDO',
                'sql',
                "SELECT * FROM tests WHERE id = ?"
            )->withExactTags(array_merge($this->baseTags(), [])),
            SpanAssertion::build(
                'PDOStatement.execute',
                'PDO',
                'sql',
                "SELECT * FROM tests WHERE id = ?"
            )->withExactTags(array_merge($this->baseTags(), [
                'db.rowcount' => 1,
            ])),
        ]);
    }

    public function testPDOStatementError()
    {
        $query = "WRONG QUERY";
        $traces = $this->withTracer(function () use ($query) {
            try {
                $pdo = $this->pdoInstance();
                $stmt = $pdo->prepare($query);
                $stmt->execute([1]);
                $stmt->fetchAll();
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.prepare', 'PDO', 'sql', "WRONG QUERY")
                ->withExactTags(array_merge($this->baseTags(), [])),
            SpanAssertion::build('PDOStatement.execute', 'PDO', 'sql', "WRONG QUERY")
                ->setError()
                    ->withExactTags(array_merge($this->baseTags(), [
                        'db.rowcount' => 0,
                        'error.msg' => 'SQL error: 42000. Driver error: 1064',
                        'error.type' => 'PDOStatement error',
                    ])),
        ]);
    }

    public function testPDOStatementException()
    {
        $query = "WRONG QUERY";
        $traces = $this->withTracer(function () use ($query) {
            try {
                $pdo = $this->pdoInstance();
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $stmt = $pdo->prepare($query);
                $stmt->execute([1]);
                $stmt->fetchAll();
            } catch (\PDOException $ex) {
            }
        });
        $this->assertSpans($traces, [
            SpanAssertion::exists('PDO.__construct'),
            SpanAssertion::build('PDO.prepare', 'PDO', 'sql', "WRONG QUERY")
                ->withExactTags(array_merge($this->baseTags(), [])),
            SpanAssertion::build('PDOStatement.execute', 'PDO', 'sql', "WRONG QUERY")
                ->setError()
                ->withExactTags(array_merge($this->baseTags(), [
                    'error.msg' => 'Sql error',
                    'error.type' => 'PDOException',
                ])),
        ]);
    }

    private function pdoInstance()
    {
        return new \PDO($this->mysqlDns(), MYSQL_USER, MYSQL_PASSWORD);
    }

    private function setUpDatabase()
    {
        $this->withTracer(function () {
            $pdo = $this->pdoInstance();
            $pdo->beginTransaction();
            $pdo->exec("
                CREATE TABLE tests (
                    id integer not null primary key,
                    name varchar(100)
                )
            ");
            $pdo->exec("INSERT INTO tests (id, name) VALUES (1, 'Tom')");
            $pdo->commit();
        });
    }

    private function clearDatabase()
    {
        $this->withTracer(function () {
            $pdo = $this->pdoInstance();
            $pdo->beginTransaction();
            $pdo->exec("DROP TABLE tests");
            $pdo->commit();
        });
    }

    public function mysqlDns()
    {
        return $dsn = "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE;
    }

    private function baseTags()
    {
        return [
            'db.engine' => 'mysql',
            'out.host' => MYSQL_HOST,
            'db.name' => MYSQL_DATABASE,
            'db.user' => MYSQL_USER,
        ];
    }
}
