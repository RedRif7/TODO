<?php
require_once "vendor/autoload.php";

use Carbon\Carbon;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;

class App {
    private string $title;
    private string $description;
    private Carbon $dateTime;
    private string $status;
    private Carbon $created_at;
    private string $file_path = 'todos.json';

    public function __construct(string $title, string $description, Carbon $dateTime) {
        $this->title = $title;
        $this->description = $description;
        $this->dateTime = $dateTime;
        $this->status = 'incomplete';
        $this->created_at = Carbon::now();
    }

    public function toJSON() {
        $todo = [
            'title' => $this->title,
            'description' => $this->description,
            'dateTime' => $this->dateTime->toDateTimeString(),
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(),
        ];

        if (!file_exists($this->file_path)) {
            file_put_contents($this->file_path, json_encode([]));
        }

        $todos = json_decode(file_get_contents($this->file_path), true);
        $todos[] = $todo;
        file_put_contents($this->file_path, json_encode($todos, JSON_PRETTY_PRINT));
    }

    public static function displayTodos() {
        $output = new ConsoleOutput();
        $file_path = 'todos.json';
        if (file_exists($file_path)) {
            $todos = json_decode(file_get_contents($file_path), true);
            if ($todos) {
                $table = new Table($output);
                $table->setHeaders(['Index', 'Title', 'Description', 'DateTime', 'Status', 'Created At']);

                foreach ($todos as $index => $todo) {
                    $table->addRow([
                        $index + 1,
                        $todo['title'],
                        $todo['description'],
                        $todo['dateTime'],
                        $todo['status'],
                        $todo['created_at'],
                    ]);
                }

                $table->render();
            } else {
                $output->writeln("No TODOs found.");
            }
        } else {
            $output->writeln("No TODOs file found.");
        }
    }

    public static function markAsCompleted(int $index) {
        $output = new ConsoleOutput();
        $file_path = 'todos.json';
        if (file_exists($file_path)) {
            $todos = json_decode(file_get_contents($file_path), true);
            if (isset($todos[$index - 1])) {
                $todos[$index - 1]['status'] = 'completed';
                file_put_contents($file_path, json_encode($todos, JSON_PRETTY_PRINT));
                $output->writeln("TODO at index $index marked as completed.");
            } else {
                $output->writeln("Invalid TODO index.");
            }
        } else {
            $output->writeln("No TODOs file found.");
        }
    }

    public static function deleteTodo(int $index) {
        $output = new ConsoleOutput();
        $file_path = 'todos.json';
        if (file_exists($file_path)) {
            $todos = json_decode(file_get_contents($file_path), true);
            if (isset($todos[$index - 1])) {
                unset($todos[$index - 1]);
                $todos = array_values($todos);
                file_put_contents($file_path, json_encode($todos, JSON_PRETTY_PRINT));
                $output->writeln("TODO at index $index deleted.");
            } else {
                $output->writeln("Invalid TODO index.");
            }
        } else {
            $output->writeln("No TODOs file found.");
        }
    }

    public static function updateStatus() {
        $file_path = 'todos.json';
        if (file_exists($file_path)) {
            $todos = json_decode(file_get_contents($file_path), true);
            $now = Carbon::now();
            foreach ($todos as $todo) {
                if (Carbon::parse($todo['dateTime'])->lessThan($now) && $todo['status'] == 'incomplete') {
                    $todo['status'] = 'expired';
                }
            }
            file_put_contents($file_path, json_encode($todos, JSON_PRETTY_PRINT));
        }
    }
}

$output = new ConsoleOutput();

while (true) {
    $output->writeln("TODO Application Menu:");
    $output->writeln("1. Create a new TODO");
    $output->writeln("2. Display all TODOs");
    $output->writeln("3. Mark a TODO as completed");
    $output->writeln("4. Delete a TODO");
    $output->writeln("5. Exit");
    $choice = readline("Select an option: ");

    switch ($choice) {
        case 1:
            $title = readline("Enter title: ");
            $description = readline("Enter description: ");
            $dateTime = Carbon::parse(readline("Enter date and time (YYYY-MM-DD HH:MM:SS): "));
            $newTodo = new App($title, $description, $dateTime);
            $newTodo->toJSON();
            echo "New TODO created successfully.\n";
            break;
        case 2:
            App::updateStatus();
            App::displayTodos();
            break;
        case 3:
            $index = (int) readline("Enter TODO index to mark as completed: ");
            App::markAsCompleted($index);
            break;
        case 4:
            $index = (int) readline("Enter TODO index to delete: ");
            App::deleteTodo($index);
            break;
        case 5:
            exit("Exiting app.\n");
        default:
            echo "Error: Invalid option, try again.\n";
            break;
    }
}
