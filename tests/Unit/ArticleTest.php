<?php

namespace Tests\Unit;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleTest
 *
 * Auto-generated from App\Schema\ArticleSchema.
 * Covers: migration, model wiring, validation, persistence.
 */
class ArticleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_articles_table_from_schema_annotations(): void
    {
        $this->assertTrue(Schema::hasTable('articles'));
    }

    #[Test]
    public function it_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('articles', 'role_id'), 'Column [role_id] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'title'), 'Column [title] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'slug'), 'Column [slug] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'content'), 'Column [content] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'excerpt'), 'Column [excerpt] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'status'), 'Column [status] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'published_at'), 'Column [published_at] missing.');
        $this->assertTrue(Schema::hasColumn('articles', 'views'), 'Column [views] missing.');
    }

    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        $model = new Article;
        $this->assertContains('role_id', $model->getFillable(), '[role_id] should be fillable.');
        $this->assertContains('title', $model->getFillable(), '[title] should be fillable.');
        $this->assertContains('slug', $model->getFillable(), '[slug] should be fillable.');
        $this->assertContains('content', $model->getFillable(), '[content] should be fillable.');
        $this->assertContains('excerpt', $model->getFillable(), '[excerpt] should be fillable.');
        $this->assertContains('status', $model->getFillable(), '[status] should be fillable.');
        $this->assertContains('published_at', $model->getFillable(), '[published_at] should be fillable.');
    }

    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        $casts = (new Article)->getCasts();
        $this->assertArrayHasKey('published_at', $casts);
        $this->assertSame('datetime', $casts['published_at']);
        $this->assertArrayHasKey('views', $casts);
        $this->assertSame('integer', $casts['views']);
    }

    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        $this->assertSame('articles', (new Article)->getTable());
    }

    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        $errors = $this->schemaValidate([]);
        $this->assertTrue($errors->has('role_id'), '[role_id] should fail required.');
        $this->assertTrue($errors->has('title'), '[title] should fail required.');
        $this->assertTrue($errors->has('content'), '[content] should fail required.');
    }

    #[Test]
    public function validation_fails_when_status_is_not_in_allowed_values(): void
    {
        $data = $this->validData();
        $data['status'] = '__invalid__';

        $errors = $this->schemaValidate($data);

        $this->assertTrue($errors->has('status'));
    }

    #[Test]
    public function it_can_create_a_article(): void
    {
        $model = Article::create($this->createData());

        $this->assertNotNull($model->id);
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    #[Test]
    public function soft_delete_works(): void
    {
        $model = Article::create($this->createData());
        $id = $model->id;

        $model->delete();

        $this->assertNull(Article::find($id));
        $this->assertNotNull(Article::withTrashed()->find($id)?->deleted_at);
    }

    #[Test]
    public function soft_deleted_record_can_be_restored(): void
    {
        $model = Article::create($this->createData());
        $model->delete();
        $model->restore();

        $this->assertNotNull(Article::find($model->id));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal valid data that passes all validation rules. */
    private function validData(): array
    {
        return [
            'title' => 'aa',
            'slug' => 'aa',
            'content' => 'aa',
            'excerpt' => null,
            'status' => 'draft',
            'published_at' => now()->toDateString(),
        ];
    }

    /**
     * Data suitable for Model::create().
     * Uses factory()->raw() to preserve hidden fields (e.g. password)
     * that toArray() would strip out.
     */
    private function createData(): array
    {
        return Article::factory()->raw();
    }

    private function schemaValidate(array $data, array $ignoreUniqueFor = [], bool $skipMissing = false): MessageBag
    {
        return Article::schemaValidate($data, $ignoreUniqueFor, $skipMissing);
    }
}
