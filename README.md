# ArrayQuery

**ArrayQuery** is a PHP class that lets you query multidimensional arrays like you would query a SQL database. It's great for filtering, sorting, joining, grouping, and paginating data without needing a real database.

---

## 🚀 Features

* `SELECT` specific columns
* `WHERE` with conditions and parameters
* `JOIN` arrays together (supports `INNER` and `LEFT` join)
* `ORDER BY` ascending or descending
* `GROUP BY` with aggregation (`SUM`, `AVG`, `COUNT`, `MAX`, `MIN`)
* `LIMIT` and `PAGINATE`
* Export as JSON or CSV

---

## 🔧 Installation

No installation required. Just include the class:

```php
require_once 'ArrayQuery.php';
```

---

## 📚 Usage Examples

### 1. Setup Your Data

```php
$users = [
  ['id' => 1, 'name' => 'Alice', 'role_id' => 10, 'dept_id' => 100, 'salary' => 70000],
  ['id' => 2, 'name' => 'Bob', 'role_id' => 20, 'dept_id' => 200, 'salary' => 55000],
  ['id' => 3, 'name' => 'Eve', 'role_id' => 10, 'dept_id' => 100, 'salary' => 72000],
];

$roles = [
  ['id' => 10, 'title' => 'Admin'],
  ['id' => 20, 'title' => 'Editor'],
];

$departments = [
  ['id' => 100, 'name' => 'Engineering'],
  ['id' => 200, 'name' => 'Marketing'],
];
```

### 2. SELECT specific columns

```php
$results = (new ArrayQuery($users))
  ->select(['name', 'salary'])
  ->get();
```

### 3. WHERE with conditions and parameters

```php
$results = (new ArrayQuery($users))
  ->where("salary > ? AND name != '?'", [60000, 'Bob'])
  ->get();
```

### 4. JOIN arrays together (INNER and LEFT)

```php
$results = (new ArrayQuery($users))
  ->join($roles, 'role_id', '=', 'id', 'inner', 'r')
  ->join($departments, 'dept_id', '=', 'id', 'left', 'd')
  ->select(['name', 'r.title', 'd.name'])
  ->get();
```

### 5. ORDER BY ascending or descending

```php
$results = (new ArrayQuery($users))
  ->orderBy('salary', 'desc')
  ->select(['name', 'salary'])
  ->get();
```

### 6. GROUP BY with aggregation (SUM, AVG, COUNT, MAX, MIN)

```php
$grouped = (new ArrayQuery($users))
  ->join($departments, 'dept_id', '=', 'id', 'left', 'd')
  ->groupBy('d.name');

foreach ($grouped as $department => $group) {
  $summary = (new ArrayQuery($group))->aggregate([
    'salary' => 'AVG',
    'id' => 'COUNT'
  ]);
  echo "$department: Avg Salary = {$summary['salary']}, Employees = {$summary['id']}\n";
}
```

### 7. LIMIT and PAGINATE

```php
// LIMIT
$results = (new ArrayQuery($users))
  ->limit(2)
  ->get();

// PAGINATE
$page = (new ArrayQuery($users))
  ->orderBy('name')
  ->paginate(1, 2); // Page 1, 2 users per page

print_r($page['data']);
```

### 8. Exporting Data

```php
$query = new ArrayQuery($users);
file_put_contents('users.json', $query->toJson());
$query->toCsv('users.csv');
```

### 9. Using Plain SQL-like Syntax

```php
$query = new ArrayQuery($users);

$results = $query
  ->join($roles, 'role_id', '=', 'id', 'inner', 'r')
  ->where("r.title = '?' AND name != '?'", ['Admin', 'Bob'])
  ->select(['id', 'name', 'r.title'])
  ->orderBy('id', 'desc')
  ->limit(2)
  ->get();
```

This is equivalent to:

```sql
SELECT id, name, r.title
FROM users
INNER JOIN roles r ON users.role_id = r.id
WHERE r.title = 'Admin' AND name != 'Bob'
ORDER BY id DESC
LIMIT 2
```

---

## 🛠 API Reference

### `select(array $columns)`

Select specific fields from each row.

### `where(string $expression, array $params)`

SQL-like where condition with placeholders (use `?`).

### `orderBy(string $column, string $direction = 'asc')`

Sorts the results.

### `limit(int $count, int $offset = 0)`

Restricts the number of results.

### `join(array $other, string $localKey, string $operator, string $foreignKey, string $type = 'inner', string $alias = null)`

Merges another array into the main data based on a key.

### `groupBy(string $column)`

Groups rows by a column.

### `aggregate(array $fields)`

Calculates SUM, AVG, COUNT, MAX, MIN.

### `paginate(int $page, int $perPage)`

Returns a paginated subset of the data.

### `toJson()`

Returns JSON.

### `toCsv($filename)`

Saves CSV to file.

### `get()`

Returns the final filtered array.

---

## 🧠 Tips

* You can chain methods like SQL: `select()->where()->join()->orderBy()->get()`
* Use aliases (e.g. `r.title`, `d.name`) to prevent key collisions after joins
* Always call `get()` at the end to execute and retrieve results

---

## 📄 License

MIT — use it freely in personal or commercial projects.

---

Happy querying! 🎯
