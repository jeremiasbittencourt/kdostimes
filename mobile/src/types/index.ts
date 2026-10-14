export type Position = 'GOL' | 'ZAG' | 'LAT' | 'MEI' | 'ATA';
export type MemberRole = 'admin' | 'member';
export type PresenceStatus = 'confirmed' | 'declined';
export type SessionStatus = 'open' | 'closed' | 'finished';
export type VotingSessionStatus = 'pending' | 'open' | 'closed';

export type NotificationEvent =
  | 'session_opened'
  | 'presence_deadline_reminder'
  | 'draw_published'
  | 'voting_opened'
  | 'voting_deadline_reminder'
  | 'voting_closed'
  | 'member_removed';

export type PlayerPosition = {
  position: Position;
  label: string;
  priority: 1 | 2;
};

export type NotificationPreference = {
  event_type: NotificationEvent;
  enabled: boolean;
};

export type User = {
  id: number;
  name: string;
  email: string;
  skill_rating: number;
  avatar_url: string | null;
  positions?: PlayerPosition[];
  notification_preferences?: NotificationPreference[];
};

export type PeladaMember = {
  id: number;
  user_id: number;
  role: MemberRole;
  is_fixed_goalkeeper: boolean;
  joined_at: string | null;
  user?: User;
};

export type Pelada = {
  id: number;
  short_code: string;
  name: string;
  location: string | null;
  day_of_week: number;
  time: string;
  players_per_team: number;
  has_fixed_goalkeepers: boolean;
  confirmation_deadline_hours: number;
  admin_id: number;
  admin?: User;
  members?: PeladaMember[];
};

export type PresenceConfirmation = {
  id: number;
  user_id: number;
  status: PresenceStatus;
  confirmed_at: string | null;
  user?: User;
};

export type PeladaSession = {
  id: number;
  pelada_id: number;
  scheduled_at: string;
  deadline_at: string;
  status: SessionStatus;
  presences?: PresenceConfirmation[];
};

export type AuthPayload = {
  user: User;
  token: string;
};

export type DrawTeamPlayer = {
  id: number;
  user_id: number;
  assigned_position: Position | null;
  is_goalkeeper: boolean;
  user?: User;
};

export type DrawTeam = {
  id: number;
  name: string;
  total_rating: number;
  players?: DrawTeamPlayer[];
};

export type Draw = {
  id: number;
  pelada_session_id: number;
  published: boolean;
  published_at: string | null;
  created_at: string | null;
  teams?: DrawTeam[];
  warnings?: string[];
};

export type VoteCategoryType = 'positive' | 'humor';

export type VotingCategory = {
  id: number;
  code: string;
  name: string;
  description: string | null;
  type: VoteCategoryType;
  points_per_vote: number;
  bonus_for_winner: number;
};

export type MyVote = {
  category_id: number;
  voted_user_id: number;
};

export type VotingSession = {
  id: number;
  pelada_session_id: number;
  opens_at: string;
  closes_at: string;
  status: VotingSessionStatus;
  categories?: VotingCategory[];
  my_votes?: MyVote[];
};

export type RankingPeriod = 'all' | 'month' | 'year' | 'last10';

export type RankingCategoryStat = {
  points: number;
  wins: number;
};

export type RankingRow = {
  user_id: number;
  name: string | null;
  avatar_url: string | null;
  total_points: number;
  wins_count: number;
  per_category: Record<string, RankingCategoryStat>;
};
