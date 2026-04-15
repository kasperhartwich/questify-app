<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Checkpoint;
use App\Models\Quest;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->firstOrCreate(
            ['email' => 'quest-creator@questify.app'],
            [
                'name' => 'Questify Team',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        foreach ($this->quests() as $questData) {
            $category = Category::query()->firstOrCreate(
                ['slug' => $questData['category_slug']],
                [
                    'name' => $questData['category_name'],
                    'description' => $questData['category_description'],
                ],
            );

            $quest = Quest::query()->updateOrCreate(
                ['title' => $questData['title'], 'creator_id' => $creator->id],
                [
                    'category_id' => $category->id,
                    'description' => $questData['description'],
                    'difficulty' => $questData['difficulty'],
                    'status' => QuestStatus::Published,
                    'visibility' => QuestVisibility::Public,
                    'wrong_answer_behaviour' => $questData['wrong_answer_behaviour'],
                    'estimated_duration_minutes' => $questData['estimated_duration_minutes'],
                    'checkpoint_arrival_radius_meters' => 50,
                    'scoring_points_per_correct' => 100,
                    'scoring_speed_bonus_enabled' => true,
                    'scoring_wrong_attempt_penalty_enabled' => false,
                    'scoring_quest_completion_time_bonus_enabled' => false,
                    'published_at' => now(),
                ],
            );

            foreach ($questData['checkpoints'] as $cpIndex => $cpData) {
                $checkpoint = Checkpoint::query()->updateOrCreate(
                    ['quest_id' => $quest->id, 'sort_order' => $cpIndex],
                    [
                        'title' => $cpData['title'],
                        'description' => $cpData['description'] ?? null,
                        'latitude' => $cpData['latitude'],
                        'longitude' => $cpData['longitude'],
                        'hint' => $cpData['hint'] ?? null,
                    ],
                );

                foreach ($cpData['questions'] as $qIndex => $qData) {
                    $question = Question::query()->updateOrCreate(
                        ['checkpoint_id' => $checkpoint->id, 'sort_order' => $qIndex],
                        [
                            'type' => $qData['type'],
                            'body' => $qData['body'],
                            'hint' => $qData['hint'] ?? null,
                            'points' => $qData['points'] ?? 10,
                        ],
                    );

                    $question->answers()->delete();
                    foreach ($qData['answers'] as $aIndex => $aData) {
                        Answer::query()->create([
                            'question_id' => $question->id,
                            'body' => $aData['body'],
                            'is_correct' => $aData['is_correct'],
                            'sort_order' => $aIndex,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function quests(): array
    {
        return [
            [
                'title' => 'Copenhagen Old Town Highlights',
                'description' => 'Explore the historic heart of Copenhagen! Walk past iconic landmarks, learn fascinating facts, and discover hidden stories from centuries of Danish history.',
                'difficulty' => Difficulty::Easy,
                'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryFree,
                'estimated_duration_minutes' => 45,
                'category_slug' => 'history',
                'category_name' => 'History',
                'category_description' => 'Quests about historical events, landmarks, and heritage.',
                'checkpoints' => [
                    [
                        'title' => 'City Hall Square',
                        'description' => 'Start your journey at the heart of Copenhagen.',
                        'latitude' => 55.6761,
                        'longitude' => 12.5683,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'In what year was Copenhagen City Hall completed?',
                                'points' => 10,
                                'answers' => [
                                    ['body' => '1905', 'is_correct' => true],
                                    ['body' => '1850', 'is_correct' => false],
                                    ['body' => '1923', 'is_correct' => false],
                                    ['body' => '1878', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'The Round Tower',
                        'description' => 'One of Europe\'s oldest functioning observatories.',
                        'latitude' => 55.6812,
                        'longitude' => 12.5757,
                        'hint' => 'Look for the famous spiral ramp inside.',
                        'questions' => [
                            [
                                'type' => QuestionType::TrueFalse,
                                'body' => 'The Round Tower was built by King Christian IV.',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'True', 'is_correct' => true],
                                    ['body' => 'False', 'is_correct' => false],
                                ],
                            ],
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'What is the Round Tower famous for inside?',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'A spiral ramp instead of stairs', 'is_correct' => true],
                                    ['body' => 'A giant pendulum', 'is_correct' => false],
                                    ['body' => 'An underground lake', 'is_correct' => false],
                                    ['body' => 'A hidden chapel', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Nyhavn',
                        'description' => 'The colourful waterfront that defines Copenhagen.',
                        'latitude' => 55.6797,
                        'longitude' => 12.5913,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'Which famous author lived at Nyhavn 20?',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'Hans Christian Andersen', 'is_correct' => true],
                                    ['body' => 'Søren Kierkegaard', 'is_correct' => false],
                                    ['body' => 'Karen Blixen', 'is_correct' => false],
                                    ['body' => 'Niels Bohr', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Tivoli Gardens Science Walk',
                'description' => 'A fun science quiz around the world-famous Tivoli Gardens area. Perfect for curious minds of all ages!',
                'difficulty' => Difficulty::Medium,
                'wrong_answer_behaviour' => WrongAnswerBehaviour::RetryPenalty,
                'estimated_duration_minutes' => 30,
                'category_slug' => 'science',
                'category_name' => 'Science',
                'category_description' => 'Quests about science, nature, and discovery.',
                'checkpoints' => [
                    [
                        'title' => 'Tivoli Main Entrance',
                        'description' => 'The iconic entrance on Vesterbrogade.',
                        'latitude' => 55.6736,
                        'longitude' => 12.5681,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'Tivoli Gardens opened in 1843. Which famous park did it later inspire?',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'Disneyland', 'is_correct' => true],
                                    ['body' => 'Central Park', 'is_correct' => false],
                                    ['body' => 'Hyde Park', 'is_correct' => false],
                                    ['body' => 'Gorky Park', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'H.C. Ørsted Park',
                        'description' => 'Named after the physicist who discovered electromagnetism.',
                        'latitude' => 55.6800,
                        'longitude' => 12.5600,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'What did Hans Christian Ørsted discover in 1820?',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'The relationship between electricity and magnetism', 'is_correct' => true],
                                    ['body' => 'The structure of the atom', 'is_correct' => false],
                                    ['body' => 'The speed of light', 'is_correct' => false],
                                    ['body' => 'Radioactivity', 'is_correct' => false],
                                ],
                            ],
                            [
                                'type' => QuestionType::TrueFalse,
                                'body' => 'The SI unit "oersted" is a unit of magnetic field strength.',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'True', 'is_correct' => true],
                                    ['body' => 'False', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Planetarium',
                        'description' => 'The Tycho Brahe Planetarium on the lake shore.',
                        'latitude' => 55.6714,
                        'longitude' => 12.5580,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'Tycho Brahe was a famous Danish figure in which field?',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'Astronomy', 'is_correct' => true],
                                    ['body' => 'Philosophy', 'is_correct' => false],
                                    ['body' => 'Medicine', 'is_correct' => false],
                                    ['body' => 'Architecture', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Christianshavn Canal Challenge',
                'description' => 'Navigate the charming canals of Christianshavn and test your knowledge about this unique neighbourhood. A harder quest for seasoned explorers!',
                'difficulty' => Difficulty::Hard,
                'wrong_answer_behaviour' => WrongAnswerBehaviour::ThreeStrikesHint,
                'estimated_duration_minutes' => 60,
                'category_slug' => 'culture',
                'category_name' => 'Culture',
                'category_description' => 'Quests about art, culture, and local traditions.',
                'checkpoints' => [
                    [
                        'title' => 'Church of Our Saviour',
                        'description' => 'The church with the famous external spiral staircase.',
                        'latitude' => 55.6726,
                        'longitude' => 12.5940,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'How many steps lead to the top of the Church of Our Saviour\'s spire?',
                                'points' => 15,
                                'hint' => 'It\'s between 350 and 450.',
                                'answers' => [
                                    ['body' => '400', 'is_correct' => true],
                                    ['body' => '256', 'is_correct' => false],
                                    ['body' => '512', 'is_correct' => false],
                                    ['body' => '321', 'is_correct' => false],
                                ],
                            ],
                            [
                                'type' => QuestionType::TrueFalse,
                                'body' => 'The spiral of the spire turns counter-clockwise when viewed from below.',
                                'points' => 10,
                                'answers' => [
                                    ['body' => 'True', 'is_correct' => true],
                                    ['body' => 'False', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Christiania Entrance',
                        'description' => 'The entrance to the famous Freetown Christiania.',
                        'latitude' => 55.6714,
                        'longitude' => 12.5970,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'In which year was Freetown Christiania established?',
                                'points' => 15,
                                'answers' => [
                                    ['body' => '1971', 'is_correct' => true],
                                    ['body' => '1965', 'is_correct' => false],
                                    ['body' => '1980', 'is_correct' => false],
                                    ['body' => '1958', 'is_correct' => false],
                                ],
                            ],
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'What was the area used for before Christiania was established?',
                                'points' => 15,
                                'answers' => [
                                    ['body' => 'A military barracks', 'is_correct' => true],
                                    ['body' => 'A fishing harbour', 'is_correct' => false],
                                    ['body' => 'A hospital', 'is_correct' => false],
                                    ['body' => 'A royal garden', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'The Opera House',
                        'description' => 'Copenhagen\'s striking waterfront opera house.',
                        'latitude' => 55.6815,
                        'longitude' => 12.6012,
                        'questions' => [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'body' => 'Who donated the funds to build the Copenhagen Opera House?',
                                'points' => 15,
                                'answers' => [
                                    ['body' => 'A.P. Møller Foundation', 'is_correct' => true],
                                    ['body' => 'Carlsberg Foundation', 'is_correct' => false],
                                    ['body' => 'Novo Nordisk Foundation', 'is_correct' => false],
                                    ['body' => 'The Danish Government', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
