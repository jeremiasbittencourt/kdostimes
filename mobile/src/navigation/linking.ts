import type {LinkingOptions} from '@react-navigation/native';
import type {AppStackParamList} from './types';

export const linking: LinkingOptions<AppStackParamList> = {
  prefixes: ['cadeostimes://', 'https://cadeostimes.app'],
  config: {
    screens: {
      Home: 'home',
      PeladaDetail: 'peladas/:peladaId',
      CreatePelada: 'peladas/new',
      JoinPelada: 'peladas/join',
      Presence: 'sessions/:sessionId/presence',
      Draw: 'sessions/:sessionId/draw',
      Voting: 'sessions/:sessionId/voting',
      Ranking: 'peladas/:peladaId/ranking',
      Profile: 'profile',
    },
  },
};
