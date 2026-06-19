<?php
require_once __DIR__.'/../../core/BaseController.php';

class CommunityController extends BaseController {

  public function getPosts() {
    try {
      $user = $this->auth->authenticate();
      
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
      $pagination = $this->paginate($page, $limit);
      
      // Get total count
      $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM community_posts WHERE society_id = ?");
      $countStmt->execute([$user['society_id']]);
      $total = $countStmt->fetch()['count'];
      
      $sql = "
        SELECT p.*, u.name as user_name, u.profile_image as avatar_url, f.flat_number as unit
        FROM community_posts p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN flats f ON (f.owner_id = u.id OR f.tenant_id = u.id)
        WHERE p.society_id = :society_id
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
      ";
      
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':society_id', $user['society_id']);
      $stmt->bindValue(':limit', $pagination['limit'], PDO::PARAM_INT);
      $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
      $stmt->execute();
      $posts = $stmt->fetchAll();
      
      // Fetch if current user liked the post
      foreach ($posts as &$post) {
          $likeStmt = $this->db->prepare("SELECT id FROM community_post_likes WHERE post_id = ? AND user_id = ?");
          $likeStmt->execute([$post['id'], $user['uid']]);
          $post['has_liked'] = $likeStmt->fetch() ? true : false;
          
          // Fallback avatar
          if (!$post['avatar_url']) {
             $post['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($post['user_name'] ?? 'User') . '&background=random';
          }
          if (!$post['unit']) {
             $post['unit'] = 'N/A';
          }
      }
      
      $this->sendPaginatedResponse($posts, $total, $pagination, "Posts retrieved successfully");
      
    } catch(Exception $e) {
      error_log("Get posts error: " . $e->getMessage());
      Response::error("Failed to retrieve posts: " . $e->getMessage(), 500);
    }
  }

  public function createPost() {
    try {
      $user = $this->auth->authenticate();
      $data = json_decode(file_get_contents("php://input"), true);
      
      $errors = $this->validateRequiredFields($data, ['content']);
      if (!empty($errors)) {
        Response::validationError($errors);
      }
      
      $postId = $this->insert('community_posts', [
        'user_id' => $user['uid'],
        'society_id' => $user['society_id'],
        'content' => $data['content'],
        'image' => $data['image'] ?? null
      ]);
      
      Response::success("Post created successfully", ['post_id' => $postId], 201);
      
    } catch(Exception $e) {
      error_log("Create post error: " . $e->getMessage());
      Response::error("Failed to create post: " . $e->getMessage(), 500);
    }
  }

  public function deletePost($postId) {
    try {
      $user = $this->auth->authenticate();
      
      $stmt = $this->db->prepare("SELECT user_id FROM community_posts WHERE id = ? AND society_id = ?");
      $stmt->execute([$postId, $user['society_id']]);
      $post = $stmt->fetch();
      
      if (!$post) {
          Response::notFound("Post not found");
      }
      
      // Admin or post author can delete
      if ($user['role'] !== 'admin' && $post['user_id'] != $user['uid']) {
          Response::error("Unauthorized to delete this post", 403);
      }
      
      $this->delete('community_posts', 'id = ?', [$postId]);
      
      Response::success("Post deleted successfully");
    } catch(Exception $e) {
      error_log("Delete post error: " . $e->getMessage());
      Response::error("Failed to delete post: " . $e->getMessage(), 500);
    }
  }

  public function likePost($postId) {
    try {
      $user = $this->auth->authenticate();
      
      $stmt = $this->db->prepare("SELECT id FROM community_post_likes WHERE post_id = ? AND user_id = ?");
      $stmt->execute([$postId, $user['uid']]);
      $like = $stmt->fetch();
      
      if ($like) {
          // Unlike
          $this->delete('community_post_likes', 'post_id = ? AND user_id = ?', [$postId, $user['uid']]);
          $this->db->query("UPDATE community_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = $postId");
          Response::success("Post unliked");
      } else {
          // Like
          $this->insert('community_post_likes', [
             'post_id' => $postId,
             'user_id' => $user['uid']
          ]);
          $this->db->query("UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = $postId");
          Response::success("Post liked");
      }
      
    } catch(Exception $e) {
      error_log("Like post error: " . $e->getMessage());
      Response::error("Failed to like/unlike post: " . $e->getMessage(), 500);
    }
  }
}
