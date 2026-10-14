export type AuthStackParamList = {
  Login: undefined;
  Register: undefined;
  ForgotPassword: undefined;
};

export type AppStackParamList = {
  Home: undefined;
  PeladaDetail: {peladaId: number};
  CreatePelada: undefined;
  JoinPelada: {inviteToken?: string} | undefined;
  Presence: {sessionId: number; peladaId: number};
  Draw: {sessionId: number; peladaId: number};
  Voting: {sessionId: number; peladaId: number};
  Ranking: {peladaId: number};
  Profile: undefined;
};
